<?php

namespace Proxy;

use Relay\RelayBuilder;
use Laminas\Diactoros\Uri;
use Proxy\Adapter\AdapterInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use Proxy\Exception\UnexpectedValueException;

class Proxy
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var callable[]
     */
    protected $filters = [];

    /**
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Prepare the proxy to forward a request instance.
     *
     * @param  RequestInterface $request
     * @return $this
     */
    public function forward(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Forward the request to the target url and return the response.
     *
     * @param  string $target
     * @throws UnexpectedValueException
     * @return ResponseInterface
     */
    public function to(string $target, string $stripRequestUriPathPrefix='')
    {
        if ($this->request === null) {
            throw new UnexpectedValueException('Missing request instance.');
        }

        $target = new Uri($target);

        // Overwrite target scheme, host and port.
        $uri = $this->request->getUri()
            ->withScheme($target->getScheme())
            ->withHost($target->getHost())
            ->withPort($target->getPort());

        if ($stripRequestUriPathPrefix) {
            if (substr($stripRequestUriPathPrefix, -1) !== '/') {
                throw new UnexpectedValueException('stripRequestUriPathPrefix must end with "/".');
            }

            $path = $uri->getPath();
            $stripRequestUriPathPrefix = ltrim($stripRequestUriPathPrefix, '/');
            if ($path && $stripRequestUriPathPrefix) {
                $pathIsRooted = substr($path, 0, 1) === '/';
                $pos = strpos($path, $stripRequestUriPathPrefix);
                if ($pathIsRooted && 1 === $pos || !$pathIsRooted && 0 === $pos) {
                    $path = substr_replace($path, '', $pos, strlen($stripRequestUriPathPrefix));
                    $uri = $uri->withPath($path);
                }
            }
        }

        // Check for subdirectory.
        if ($path = $target->getPath()) {
            $uri = $uri->withPath(rtrim($path, '/') . '/' . ltrim($uri->getPath(), '/'));
        }

        $request = $this->request->withUri($uri);

        $stack = $this->filters;

        $stack[] = function (RequestInterface $request, callable $next) {
            try {
                $response = $this->adapter->send($request);
            } catch (ClientException $ex) {
                $response = $ex->getResponse();
            }

            return $response;
        };

        $relay = (new RelayBuilder)->newInstance($stack);

        return $relay->handle($request);
    }

    /**
     * Add a filter middleware.
     *
     * @param  callable $callable
     * @return $this
     */
    public function filter(callable $callable)
    {
        $this->filters[] = $callable;

        return $this;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
