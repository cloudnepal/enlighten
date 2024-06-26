<?php

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use Styde\Enlighten\Models\Example;
use Styde\Enlighten\Models\ExampleRequest;
use Tests\Integration\App\Models\User;

class PostRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfig([
            'auth.providers.users.model' => User::class
        ]);
    }

    #[Test]
    function creates_an_example_of_a_post_request(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->post('user', [
            'name' => 'Duilio',
            'email' => 'duilio@example.test',
            'password' => 'my-password',
        ]);

        $response->assertRedirect('/');

        $this->assertCredentials([
            'name' => 'Duilio',
            'email' => 'duilio@example.test',
            'password' => 'my-password',
        ]);

        tap(Example::first(), function (Example $example) {
            tap($example->requests->first(), function (?ExampleRequest $request) {
                $this->assertNotNull($request);

                $this->assertSame('POST', $request->request_method);
                $this->assertSame('user', $request->request_path);
                $this->assertSame('user', $request->route);

                $this->assertSame([
                    'name' => 'Duilio',
                    'email' => 'duilio@example.test',
                    'password' => 'my-password',
                ], $request->request_input);

                $this->assertTrue($request->has_redirection_status);
            });
        });
    }

    #[Test]
    function saves_validation_errors(): void
    {
        $response = $this->post('user', [
            'name' => 'Duilio',
            'password' => 'my-password',
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseMissing('users', []);

        tap(Example::first()->requests()->first(), function (?ExampleRequest $request) {
            $this->assertTrue($request->has_redirection_status);

            $this->assertSame([
                'default' => [
                    'email' => ['The email field is required.'],
                ],
            ], $request->validation_errors);
        });
    }
}
