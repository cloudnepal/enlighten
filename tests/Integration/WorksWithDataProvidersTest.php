<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use Styde\Enlighten\Models\Example;
use Styde\Enlighten\Models\ExampleSnippet;

class WorksWithDataProvidersTest extends TestCase
{
    #[Test]
    #[TestWith(['dataset1'])]
    #[TestWith(['dataset2'])]
    function can_store_information_of_tests_with_data_provider_from_annotation($data): void
    {
        $this->assertTrue(str_starts_with((string) $data, 'dataset'));

        $this->saveExampleStatus();

        $example = Example::first();

        $this->assertIsArray($example->provided_data);
        $this->assertTrue(str_starts_with((string) $example->provided_data[0], 'dataset'));
    }

    #[Test]
    #[DataProvider('dataProviderMethod')]
    function can_store_information_of_tests_with_data_providers_from_method($data): void
    {
        $this->assertTrue(str_starts_with((string) $data, 'dataset'));

        $this->saveExampleStatus();

        $example = Example::first();

        $this->assertStringStartsWith('Can store information of tests with data providers from method', $example->title);
        $this->assertTrue(in_array($example->data_name, ['0', '1'], true));
        $this->assertIsArray($example->provided_data);
        $this->assertTrue(str_starts_with((string) $example->provided_data[0], 'dataset'));
    }

    public static function dataProviderMethod(): array
    {
        return [
            ['dataset1'],
            ['dataset2']
        ];
    }

    #[Test]
    #[DataProvider('dataProviderWithObjects')]
    function stores_information_of_objects_returned_by_data_providers($object): void
    {
        $this->assertInstanceOf(StdClass::class, $object);
        $this->assertSame('dataset1', $object->property);

        $this->saveExampleStatus();

        $example = Example::first();

        $expected = [
            [
                ExampleSnippet::CLASS_NAME => 'stdClass',
                ExampleSnippet::ATTRIBUTES => [
                    'property' => 'dataset1',
                ],
            ],
        ];
        $this->assertSame($expected, $example->provided_data);
    }

    public static function dataProviderWithObjects(): array
    {
        $object1 = new stdClass;
        $object1->property = 'dataset1';

        return [
            [$object1],
        ];
    }

    #[Test]
    #[DataProvider('dataProviderWithFunctions')]
    function stores_information_of_functions_returned_by_data_providers($function): void
    {
        $this->assertInstanceOf(\Closure::class, $function);
        $this->assertSame('test', $function());

        $this->saveExampleStatus();

        $example = Example::first();

        $expected = [
            'function' => [
                ExampleSnippet::FUNCTION => ExampleSnippet::ANONYMOUS_FUNCTION,
                ExampleSnippet::PARAMETERS => [],
                ExampleSnippet::RETURN_TYPE => 'string',
            ],
        ];
        $this->assertSame($expected, $example->provided_data);
    }

    public static function dataProviderWithFunctions(): array
    {
        $object1 = new stdClass;
        $object1->property = 'dataset1';

        return [
            [
                'function' => function (): string {
                    return 'test';
                },
            ]
        ];
    }

    #[Test]
    #[DataProvider('providedDataWithKeys')]
    function adds_key_from_the_data_provider_at_the_end_of_the_title($num1, $num2): void
    {
        $this->assertSame(3, $num1 + $num2);

        $this->saveExampleStatus();

        $example = Example::first();

        $this->assertSame('Adds key from the data provider at the end of the title (custom data key)', $example->title);
        $this->assertSame('custom data key', $example->data_name);
    }

    public static function providedDataWithKeys(): array
    {
        return [
           'custom data key' => [1, 2],
        ];
    }
}
