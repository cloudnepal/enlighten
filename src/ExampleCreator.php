<?php

namespace Styde\Enlighten;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionClass;
use ReflectionMethod;
use Styde\Enlighten\CodeExamples\CodeResultTransformer;
use Styde\Enlighten\Contracts\ExampleBuilder;
use Styde\Enlighten\Contracts\ExampleGroupBuilder;
use Styde\Enlighten\Contracts\RunBuilder;
use Styde\Enlighten\Models\Status;
use Styde\Enlighten\Utils\Annotations;
use Throwable;

class ExampleCreator
{
    private const LAST_ORDER_POSITION = 9999;

    protected static ?ExampleGroupBuilder $currentExampleGroupBuilder = null;

    protected ?ExampleBuilder $currentExampleBuilder = null;

    protected ?Throwable $currentException = null;

    public static function clearExampleGroupBuilder(): void
    {
        static::$currentExampleGroupBuilder = null;
    }

    public function __construct(
        private readonly RunBuilder $runBuilder,
        protected Annotations $annotations,
        protected Settings $settings,
        private readonly ExampleProfile $profile,
    ) {
    }

    public function getCurrentExample(): ?ExampleBuilder
    {
        return $this->currentExampleBuilder;
    }

    public function makeExample(string $className, string $methodName, array $providedData = null, $dataName = null): void
    {
        $this->currentExampleBuilder = null;
        $this->currentException = null;

        $classAnnotations = $this->annotations->getFromClass($className);
        $methodAnnotations = $this->annotations->getFromMethod($className, $methodName);

        $options = array_merge($classAnnotations->get('enlighten', []), $methodAnnotations->get('enlighten', []));

        if ($this->profile->shouldIgnore($className, $methodName, $options)) {
            return;
        }

        $methodAttributes = (new ReflectionMethod($className, $methodName))->getAttributes();

        $exampleGroupBuilder = $this->getExampleGroup($className, $classAnnotations);

        $this->currentExampleBuilder = $exampleGroupBuilder->newExample()
            ->setMethodName($methodName)
            ->setProvidedData(CodeResultTransformer::exportProvidedData($providedData))
            ->setDataName($dataName === '' ? null : $dataName)
            ->setSlug($this->settings->generateSlugFromMethodName($methodName))
            ->setTitle($this->getTitleFor('method', $methodAttributes, $methodAnnotations, $methodName))
            ->setDescription($methodAnnotations->get('description'))
            ->setLine($this->getStartLine($className, $methodName))
            ->setOrderNum($methodAnnotations->get('enlighten')['order'] ?? self::LAST_ORDER_POSITION);
    }

    public function addQuery(QueryExecuted $query): void
    {
        if ($this->shouldIgnore()) {
            return;
        }

        $this->currentExampleBuilder->addQuery($query);
    }

    public function captureException(Throwable $exception): void
    {
        if ($this->shouldIgnore()) {
            return;
        }

        // This will save the exception in memory without persisting it to the DB
        // We want to wait for the result from test. So, we will only persist
        // the exception data in the database if the test did not succeed.
        $this->currentException = $exception;
    }

    public function setStatus(string $testStatus): void
    {
        if ($this->shouldIgnore()) {
            return;
        }

        $status = Status::fromTestStatus($testStatus);

        $this->currentExampleBuilder->setStatus($testStatus, $status);

        if ($status !== Status::SUCCESS && $this->currentException !== null) {
            $this->currentExampleBuilder->setException(ExceptionInfo::make($this->currentException));
        }
    }

    public function build(): void
    {
        if ($this->shouldIgnore()) {
            return;
        }

        $this->currentExampleBuilder->build();
    }

    public function shouldIgnore(): bool
    {
        return is_null($this->currentExampleBuilder);
    }

    private function getTitleFor(string $type, array $attributes, Collection $annotations, string $classOrMethodName)
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === TestDox::class) {
                return $attribute->getArguments()[0];
            }
        }

        return $annotations->get('title')
            ?: $annotations->get('testdox')
            ?: $this->settings->generateTitle($type, $classOrMethodName);
    }

    private function getStartLine($className, $methodName): int
    {
        return (new ReflectionMethod($className, $methodName))->getStartLine();
    }

    private function getExampleGroup(string $className, Collection $classAnnotations): ExampleGroupBuilder
    {
        if (optional(static::$currentExampleGroupBuilder)->is($className)) {
            return static::$currentExampleGroupBuilder;
        }

        return static::$currentExampleGroupBuilder = $this->makeExampleGroup($className, $classAnnotations);
    }

    private function makeExampleGroup(string $className, Collection $classAnnotations): ExampleGroupBuilder
    {
        $classAttributes = (new ReflectionClass($className))->getAttributes();

        return $this->runBuilder->newExampleGroup()
            ->setClassName($className)
            ->setTitle($this->getTitleFor('class', $classAttributes, $classAnnotations, $className))
            ->setDescription($classAnnotations->get('description'))
            ->setArea($this->settings->getAreaSlug($className))
            ->setSlug($this->settings->generateSlugFromClassName($className))
            ->setOrderNum($classAnnotations->get('enlighten')['order'] ?? self::LAST_ORDER_POSITION);
    }
}
