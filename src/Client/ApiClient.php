<?php

namespace MelhorEnvio\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use MelhorEnvio\Exceptions\MelhorEnvioException;
use Psr\Http\Message\ResponseInterface;

class ApiClient extends Client
{

    // Identificação do app
    protected $clientId;

    protected $secretKey;

    protected $appName;

    protected $email; // Email tecnico

    protected $accessToken;

    public function __construct(array $config = [])
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapResponse(function (ResponseInterface $response) {
            return new Response(
                $response->getStatusCode(),
                $response->getHeaders(),
                $response->getBody(),
                $response->getProtocolVersion(),
                $response->getReasonPhrase());
        }));

        $this->clientId = $config['clientId'] ?? '';
        $this->secretKey = $config['secretKey'] ?? '';
        $this->accessToken = $config['accessToken'] ?? '';
        $this->email = $config['email'] ?? '';
        $this->appName = $config['appName'] ?? '';

        $config['handler'] = $stack;

        $config['headers'] = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if ($this->accessToken)
            $config['headers']['Authorization'] = "Bearer {$this->accessToken}";

        if ($this->appName && $this->email)
            $config['headers']['User-Agent'] = "{$this->appName} ({$this->email})";

        parent::__construct($config);
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        try {
            return parent::request($method, $uri, $options);

        } catch (ClientException $e) {
            throw new MelhorEnvioException(
                $e->getMessage(),
                $e->getResponse(),
                $e->getCode()
            );
        }
    }
}
