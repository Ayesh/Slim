<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Assets;

use ReflectionProperty;

/**
 * Replacement for the assertAttribute*() helpers removed in PHPUnit 9.
 */
trait ReadsAttributes
{
    /**
     * Read a (possibly non-public) property of an object.
     *
     * @param object $object
     * @param string $name
     *
     * @return mixed
     */
    protected function readAttribute(object $object, string $name): mixed {
        return (new ReflectionProperty($object, $name))->getValue($object);
    }
}
