<?php

namespace Proxy\Adapter\ReactGuzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Proxy\Adapter\AdapterInterface;
use Psr\Http\Message\RequestInterface;

class ReactGuzzleAdapter implements AdapterInterface
{
    /**
     * The Guzzle client instance.
     * @var Client
     */
    protected $client;

    /**
     * Construct Guzzle based HTTP adapter.
     * @param Client $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new Client;
    }

    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request)
    {
        $request = new Request(
            $request->getMethod(),
            (string) $request->getUri(),
            $request->getHeaders(),
            (string) $request->getBody()
        );

        $promise = $this->client->sendAsync($request);

        $response = $promise->wait();

        return $response;
    }
}
