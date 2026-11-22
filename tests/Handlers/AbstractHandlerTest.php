<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Slim\Handlers\AbstractHandler;
use Slim\Http\Request;

class AbstractHandlerTest extends TestCase
{
    public function testHalfValidContentType(): void {
        $req = $this->createStub(Request::class);

        $req->method('getHeaderLine')->willReturn('unknown/+json');

        $abstractHandler = new class extends AbstractHandler {};

        $newTypes = [
            'application/xml',
            'text/xml',
            'text/html',
        ];

        $class = new ReflectionClass(AbstractHandler::class);

        $reflectionProperty = $class->getProperty('knownContentTypes');
        $reflectionProperty->setValue($abstractHandler, $newTypes);

        $method = $class->getMethod('determineContentType');

        $return = $method->invoke($abstractHandler, $req);

        $this->assertEquals('text/html', $return);
    }

    /**
     * Ensure that an acceptable media-type is found in the Accept header even
     * if it's not the first in the list.
     */
    public function testAcceptableMediaTypeIsNotFirstInList(): void {
        $request = $this->createStub(Request::class);

        $request->method('getHeaderLine')
            ->willReturn('text/plain,text/html');

        // provide access to the determineContentType() as it's a protected method
        $class = new ReflectionClass(AbstractHandler::class);
        $method = $class->getMethod('determineContentType');

        // use a mock object here as AbstractHandler cannot be directly instantiated
        $abstractHandler = new class extends AbstractHandler {};

        // call determineContentType()
        $return = $method->invoke($abstractHandler, $request);

        $this->assertEquals('text/html', $return);
    }
}
