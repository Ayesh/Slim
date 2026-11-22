<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Uri;

class UriTest extends TestCase
{
    protected $uri;

    public function uriFactory(): Uri {
        $scheme = 'https';
        $host = 'example.com';
        $port = 443;
        $path = '/foo/bar';
        $query = 'abc=123';
        $fragment = 'section3';
        $user = 'josh';
        $password = 'sekrit';

        return new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);
    }

    public function testGetScheme(): void {
        $this->assertEquals('https', $this->uriFactory()->getScheme());
    }

    public function testWithScheme(): void {
        $uri = $this->uriFactory()->withScheme('http');

        $this->assertAttributeEquals('http', 'scheme', $uri);
    }

    public function testWithSchemeRemovesSuffix(): void {
        $uri = $this->uriFactory()->withScheme('http://');

        $this->assertAttributeEquals('http', 'scheme', $uri);
    }

    public function testWithSchemeEmpty(): void {
        $uri = $this->uriFactory()->withScheme('');

        $this->assertAttributeEquals('', 'scheme', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri scheme must be one of: "", "https", "http"
     */
    public function testWithSchemeInvalid(): void {
        $this->uriFactory()->withScheme('ftp');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri scheme must be a string
     */
    public function testWithSchemeInvalidType(): void {
        $this->uriFactory()->withScheme([]);
    }

    public function testGetAuthorityWithUsernameAndPassword(): void {
        $this->assertEquals('josh:sekrit@example.com', $this->uriFactory()->getAuthority());
    }

    public function testGetAuthorityWithUsername(): void {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh@example.com', $uri->getAuthority());
    }

    public function testGetAuthority(): void {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function testGetAuthorityWithNonStandardPort(): void {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 400;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('example.com:400', $uri->getAuthority());
    }

    public function testGetUserInfoWithUsernameAndPassword(): void {
        $scheme = 'https';
        $user = 'josh';
        $password = 'sekrit';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsername(): void {
        $scheme = 'https';
        $user = 'josh';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('josh', $uri->getUserInfo());
    }

    public function testGetUserInfoNone(): void {
        $scheme = 'https';
        $user = '';
        $password = '';
        $host = 'example.com';
        $path = '/foo/bar';
        $port = 443;
        $query = 'abc=123';
        $fragment = 'section3';
        $uri = new Uri($scheme, $host, $port, $path, $query, $fragment, $user, $password);

        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testGetUserInfoWithUsernameAndPasswordEncodesCorrectly(): void {
        $uri = Uri::createFromString('https://bob%40example.com:pass%3Aword@example.com:443/foo/bar?abc=123#section3');

        $this->assertEquals('bob%40example.com:pass%3Aword', $uri->getUserInfo());
    }

    public function testWithUserInfo(): void {
        $uri = $this->uriFactory()->withUserInfo('bob', 'pass');

        $this->assertAttributeEquals('bob', 'user', $uri);
        $this->assertAttributeEquals('pass', 'password', $uri);
    }

    public function testWithUserInfoEncodesCorrectly(): void {
        $uri = $this->uriFactory()->withUserInfo('bob@example.com', 'pass:word');

        $this->assertAttributeEquals('bob%40example.com', 'user', $uri);
        $this->assertAttributeEquals('pass%3Aword', 'password', $uri);
    }

    public function testWithUserInfoRemovesPassword(): void {
        $uri = $this->uriFactory()->withUserInfo('bob');

        $this->assertAttributeEquals('bob', 'user', $uri);
        $this->assertAttributeEquals('', 'password', $uri);
    }

    public function testWithUserInfoRemovesInfo(): void {
        $uri = $this->uriFactory()->withUserInfo('bob', 'password');

        $uri = $uri->withUserInfo('');
        $this->assertAttributeEquals('', 'user', $uri);
        $this->assertAttributeEquals('', 'password', $uri);
    }


    public function testGetHost(): void {
        $this->assertEquals('example.com', $this->uriFactory()->getHost());
    }

    public function testWithHost(): void {
        $uri = $this->uriFactory()->withHost('slimframework.com');

        $this->assertAttributeEquals('slimframework.com', 'host', $uri);
    }

    public function testGetPortWithSchemeAndNonDefaultPort(): void {
        $uri = new Uri('https', 'www.example.com', 4000);

        $this->assertEquals(4000, $uri->getPort());
    }

    public function testGetPortWithSchemeAndDefaultPort(): void {
        $uriHttp = new Uri('http', 'www.example.com', 80);
        $uriHttps = new Uri('https', 'www.example.com', 443);

        $this->assertNull($uriHttp->getPort());
        $this->assertNull($uriHttps->getPort());
    }

    public function testGetPortWithoutSchemeAndPort(): void {
        $uri = new Uri('', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testGetPortWithSchemeWithoutPort(): void {
        $uri = new Uri('http', 'www.example.com');

        $this->assertNull($uri->getPort());
    }

    public function testWithPort(): void {
        $uri = $this->uriFactory()->withPort(8000);

        $this->assertAttributeEquals(8000, 'port', $uri);
    }

    public function testWithPortNull(): void {
        $uri = $this->uriFactory()->withPort(null);

        $this->assertAttributeEquals(null, 'port', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPortInvalidInt(): void {
        $this->uriFactory()->withPort(70000);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWithPortInvalidString(): void {
        $this->uriFactory()->withPort('Foo');
    }

    public function testGetBasePathNone(): void {
        $this->assertEquals('', $this->uriFactory()->getBasePath());
    }

    public function testWithBasePath(): void {
        $uri = $this->uriFactory()->withBasePath('/base');

        $this->assertAttributeEquals('/base', 'basePath', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri path must be a string
     */
    public function testWithBasePathInvalidType(): void {
        $this->uriFactory()->withBasePath(['foo']);
    }

    public function testWithBasePathAddsPrefix(): void {
        $uri = $this->uriFactory()->withBasePath('base');

        $this->assertAttributeEquals('/base', 'basePath', $uri);
    }

    public function testWithBasePathIgnoresSlash(): void {
        $uri = $this->uriFactory()->withBasePath('/');

        $this->assertAttributeEquals('', 'basePath', $uri);
    }

    public function testGetPath(): void {
        $this->assertEquals('/foo/bar', $this->uriFactory()->getPath());
    }

    public function testWithPath(): void {
        $uri = $this->uriFactory()->withPath('/new');

        $this->assertAttributeEquals('/new', 'path', $uri);
    }

    public function testWithPathWithoutPrefix(): void {
        $uri = $this->uriFactory()->withPath('new');

        $this->assertAttributeEquals('new', 'path', $uri);
    }

    public function testWithPathEmptyValue(): void {
        $uri = $this->uriFactory()->withPath('');

        $this->assertAttributeEquals('', 'path', $uri);
    }

    public function testWithPathUrlEncodesInput(): void {
        $uri = $this->uriFactory()->withPath('/includes?/new');

        $this->assertAttributeEquals('/includes%3F/new', 'path', $uri);
    }

    public function testWithPathDoesNotDoubleEncodeInput(): void {
        $uri = $this->uriFactory()->withPath('/include%25s/new');

        $this->assertAttributeEquals('/include%25s/new', 'path', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri path must be a string
     */
    public function testWithPathInvalidType(): void {
        $this->uriFactory()->withPath(['foo']);
    }

    public function testGetQuery(): void {
        $this->assertEquals('abc=123', $this->uriFactory()->getQuery());
    }

    public function testWithQuery(): void {
        $uri = $this->uriFactory()->withQuery('xyz=123');

        $this->assertAttributeEquals('xyz=123', 'query', $uri);
    }

    public function testWithQueryRemovesPrefix(): void {
        $uri = $this->uriFactory()->withQuery('?xyz=123');

        $this->assertAttributeEquals('xyz=123', 'query', $uri);
    }

    public function testWithQueryEmpty(): void {
        $uri = $this->uriFactory()->withQuery('');

        $this->assertAttributeEquals('', 'query', $uri);
    }

    public function testFilterQuery(): void {
        $uri = $this->uriFactory()->withQuery('?foobar=%match');

        $this->assertAttributeEquals('foobar=%25match', 'query', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri query must be a string
     */
    public function testWithQueryInvalidType(): void {
        $this->uriFactory()->withQuery(['foo']);
    }

    public function testGetFragment(): void {
        $this->assertEquals('section3', $this->uriFactory()->getFragment());
    }

    public function testWithFragment(): void {
        $uri = $this->uriFactory()->withFragment('other-fragment');

        $this->assertAttributeEquals('other-fragment', 'fragment', $uri);
    }

    public function testWithFragmentRemovesPrefix(): void {
        $uri = $this->uriFactory()->withFragment('#other-fragment');

        $this->assertAttributeEquals('other-fragment', 'fragment', $uri);
    }

    public function testWithFragmentEmpty(): void {
        $uri = $this->uriFactory()->withFragment('');

        $this->assertAttributeEquals('', 'fragment', $uri);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri fragment must be a string
     */
    public function testWithFragmentInvalidType(): void {
        $this->uriFactory()->withFragment(['foo']);
    }

    public function testToString(): void {
        $uri = $this->uriFactory();

        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withBasePath('foo/');
        $this->assertEquals('https://josh:sekrit@example.com/foo/bar?abc=123#section3', (string) $uri);

        $uri = $uri->withPath('/bar');
        $this->assertEquals('https://josh:sekrit@example.com/bar?abc=123#section3', (string) $uri);

        // ensure that a Uri with just a base path correctly converts to a string
        // (This occurs via createFromEnvironment when index.php is in a subdirectory)
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/',
            'HTTP_HOST' => 'example.com',
        ]);
        $uri = Uri::createFromEnvironment($environment);
        $this->assertEquals('http://example.com/foo/', (string) $uri);
    }

    public function testCreateFromString(): void {
        $uri = Uri::createFromString('https://example.com:8080/foo/bar?abc=123');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Uri must be a string
     */
    public function testCreateFromStringWithInvalidType(): void {
        Uri::createFromString(['https://example.com:8080/foo/bar?abc=123']);
    }

    public function testCreateEnvironment(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080,
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateFromEnvironmentSetsDefaultPortWhenHostHeaderDoesntHaveAPort(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com',
            'HTTPS' => 'on',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals(null, $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());

        $this->assertEquals('https://example.com/foo/bar?abc=123', (string)$uri);
    }

    public function testCreateEnvironmentWithIPv6HostNoPort(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]',
            'REMOTE_ADDR' => '2001:db8::1',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateEnvironmentWithIPv6HostWithPort(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => '[2001:db8::1]:8080',
            'REMOTE_ADDR' => '2001:db8::1',
        ]);

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    /**
     * @group one
     */
    public function testCreateEnvironmentWithNoHostHeader(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'REMOTE_ADDR' => '2001:db8::1',
            'SERVER_NAME' => '[2001:db8::1]',
            'SERVER_PORT' => '8080',
        ]);
        $environment->remove('HTTP_HOST');

        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('josh:sekrit', $uri->getUserInfo());
        $this->assertEquals('[2001:db8::1]', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getPath());
        $this->assertEquals('abc=123', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    public function testCreateEnvironmentWithBasePath(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('/foo', $uri->getBasePath());
        $this->assertEquals('bar', $uri->getPath());

        $this->assertEquals('http://localhost/foo/bar', (string) $uri);
    }

    public function testGetBaseUrl(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com/foo', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithNoBasePath(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:80',
            'SERVER_PORT' => 80
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://example.com', $uri->getBaseUrl());
    }

    public function testGetBaseUrlWithAuthority(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/foo/index.php',
            'REQUEST_URI' => '/foo/bar',
            'PHP_AUTH_USER' => 'josh',
            'PHP_AUTH_PW' => 'sekrit',
            'QUERY_STRING' => 'abc=123',
            'HTTP_HOST' => 'example.com:8080',
            'SERVER_PORT' => 8080
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://josh:sekrit@example.com:8080/foo', $uri->getBaseUrl());
    }

    public function testWithPathWhenBaseRootIsEmpty(): void {
        $environment = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/bar',
        ]);
        $uri = Uri::createFromEnvironment($environment);

        $this->assertEquals('http://localhost/test', (string) $uri->withPath('test'));
    }

    public function testRequestURIContainsIndexDotPhp(): void {
        $uri = Uri::createFromEnvironment(
            Environment::mock(
                [
                    'SCRIPT_NAME' => '/foo/index.php',
                    'REQUEST_URI' => '/foo/index.php/bar/baz',
                ]
            )
        );
        $this->assertSame('/foo/index.php', $uri->getBasePath());
    }

    public function testRequestURICanContainParams(): void {
        $uri = Uri::createFromEnvironment(
            Environment::mock(
                [
                    'REQUEST_URI' => '/foo?abc=123',
                ]
            )
        );
        $this->assertEquals('abc=123', $uri->getQuery());
    }

    public function testUriDistinguishZeroFromEmptyString(): void {
        $expected = 'https://0:0@0:1/0?0#0';
        $this->assertSame($expected, (string) Uri::createFromString($expected));
    }

    public function testGetBaseUrlDistinguishZeroFromEmptyString(): void {
        $expected = 'https://0:0@0:1/0?0#0';
        $this->assertSame('https://0:0@0:1', (string) Uri::createFromString($expected)->getBaseUrl());
    }

    public function testConstructorWithEmptyPath(): void {
        $uri = new Uri('https', 'example.com', null, '');
        $this->assertSame('/', $uri->getPath());
    }

    public function testConstructorWithZeroAsPath(): void {
        $uri = new Uri('https', 'example.com', null, '0');
        $this->assertSame('0', (string) $uri->getPath());
    }
}
