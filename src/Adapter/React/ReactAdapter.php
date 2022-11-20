<?php

namespace Proxy\Adapter\React;

use Proxy\Adapter\AdapterInterface;
use Psr\Http\Message\RequestInterface;
use React\Http\Browser;
use function React\Async\await;

class ReactAdapter implements AdapterInterface
{
    /**
     * The React browser instance.
     * @var Browser
     */
    protected $client;

    /**
     * Construct a React based HTTP adapter.
     * @param Browser $client
     */
    public function __construct(Browser $client = null)
    {
        $this->client = $client ?: new Browser;
    }

    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request)
    {
        $promise = $this->client->request(
            $request->getMethod(),
            (string) $request->getUri(),
            $request->getHeaders(),
            (string) $request->getBody()
        );

        $response = await($promise);

        return $response;
    }
}
