<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use Styde\Enlighten\Models\Example;
use Styde\Enlighten\Models\ExampleGroup;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    function gets_the_path_to_the_file(): void
    {
        $example = new Example([
            'line' => 3,
        ]);
        $example->group = new ExampleGroup([
            'class_name' => 'Tests\Feature\Admin\CreateUsersTest',
        ]);

        $this->assertSame(1, preg_match('@phpstorm://open\?file=(.*?)Tests%2FFeature%2FAdmin%2FCreateUsersTest.php&line=3@', (string) $example->file_link));
    }

    #[Test]
    function get_the_example_url(): void
    {
        $exampleGroup = new ExampleGroup([
            'run_id' => 1,
            'slug' => 'feature-api-request'
        ]);

        $example = new Example([
            'group' => $exampleGroup,
            'slug' => 'list-users'
        ]);

        $this->assertSame('http://localhost/enlighten/run/1/feature-api-request/list-users', $example->url);
        $this->assertSame('http://localhost/enlighten/run/1/feature-api-request/list-users', $example->getUrl());
    }

    #[Test]
    function get_the_signature_of_an_example(): void
    {
        $group = $this->createExampleGroup(null, 'Namespace\NameOfTheClass');

        $example = $this->createExample($group, 'the_name_of_the_method');

        $this->assertSame('Namespace\NameOfTheClass::the_name_of_the_method', $example->signature);
        $this->assertSame('Namespace\NameOfTheClass::the_name_of_the_method', $example->getSignature());
    }
}
