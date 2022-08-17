<?php

namespace Proxy;

use Laminas\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Proxy\Adapter\Dummy\DummyAdapter;
use Psr\Http\Message\RequestInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Proxy\Exception\UnexpectedValueException;

class ProxyTest extends TestCase
{
    /**
     * @var Proxy
     */
    private $proxy;

    public function setUp(): void
    {
        $this->proxy = new Proxy(new DummyAdapter());
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    public function to_throws_exception_if_no_request_is_given()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->proxy->to('http://www.example.com');
    }

    /**
     * @test
     */
    public function to_returns_psr_response()
    {
        $response = $this->proxy->forward(ServerRequestFactory::fromGlobals())->to('http://www.example.com');

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    /**
     * @test
     */
    public function to_applies_filters()
    {
        $applied = false;

        $this->proxy->forward(ServerRequestFactory::fromGlobals())->filter(function ($request, $next) use (
            &$applied
        ) {
            $applied = true;

            return new Response('php://memory', 200);
        })->to('http://www.example.com');

        $this->assertTrue($applied);
    }

    /**
     * @test
     */
    public function to_sends_request()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/path?query=yes');
        $url = 'https://www.example.com';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url);
    }

    /**
     * @test
     */
    public function to_sends_request_with_port()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/path?query=yes');
        $url = 'https://www.example.com:3000';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com:3000/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url);
    }

    /**
     * @test
     */
    public function to_sends_request_with_subdirectory()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/path?query=yes');
        $url = 'https://www.example.com/proxy/';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com/proxy/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url);
    }

    /**
     * @test
     */
    public function to_sends_request_with_port_subdirectory()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/path?query=yes');
        $url = 'https://www.example.com:3000/proxy/';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com:3000/proxy/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url);
    }

    /**
     * @test
     */
    public function to_throws_exception_with_port_subdirectory_invalid_stripRequestUriPathPrefix()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/api/path?query=yes');
        $url = 'https://www.example.com:3000/proxy/';
        $stripRequestUriPathPrefix = 'api';

        $this->expectException(UnexpectedValueException::class);
        $this->proxy->forward($request)->to($url, $stripRequestUriPathPrefix);
    }

    /**
     * @test
     */
    public function to_sends_request_with_port_subdirectory_matching_stripRequestUriPathPrefix()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/api/path?query=yes');
        $url = 'https://www.example.com:3000/proxy/';
        $stripRequestUriPathPrefix = 'api/';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com:3000/proxy/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url, $stripRequestUriPathPrefix);
    }

    /**
     * @test
     */
    public function to_sends_request_with_port_subdirectory_nonmatching_stripRequestUriPathPrefix()
    {
        $request = (new ServerRequestFactory)->createServerRequest('GET', 'http://localhost/api1/path?query=yes');
        $url = 'https://www.example.com:3000/proxy/';
        $stripRequestUriPathPrefix = 'api2/';

        $adapter = $this->getMockBuilder(DummyAdapter::class)
            ->getMock();

        $verifyParam = $this->callback(function (RequestInterface $request) use ($url) {
            return $request->getUri() == 'https://www.example.com:3000/proxy/api1/path?query=yes';
        });

        $adapter->expects($this->once())
            ->method('send')
            ->with($verifyParam)
            ->willReturn(new Response);

        $proxy = new Proxy($adapter);
        $proxy->forward($request)->to($url, $stripRequestUriPathPrefix);
    }
}
