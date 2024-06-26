<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Styde\Enlighten\HttpExamples\RequestInspector;
use Styde\Enlighten\HttpExamples\RouteInspector;
use Tests\TestCase;

class RequestInspectorTest extends TestCase
{
    #[Test]
    function gets_the_form_data_from_the_request_without_query_parameters(): void
    {
        $request = new Request([
            'query' => 'parameter',
        ], [
            'input' => 'value',
        ]);

        $request->setMethod('POST');

        $request->setRouteResolver(function () {
            return new Route('GET', 'users', function () {
            });
        });

        $requestInspector = new RequestInspector(new RouteInspector, []);

        $input = $requestInspector->getDataFrom($request)->getInput();

        $this->assertSame(['input' => 'value'], $input);
    }
}
