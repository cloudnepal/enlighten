<?php

namespace Styde\Enlighten;

abstract class TestInfo
{
    protected string $className;
    protected string $methodName;

    public function __construct(string $className, string $methodName)
    {
        $this->className = $className;
        $this->methodName = $methodName;
    }

    public function is(string $className, string $methodName): bool
    {
        return $this->className == $className && $this->methodName == $methodName;
    }

    abstract public function isIgnored(): bool;
}
