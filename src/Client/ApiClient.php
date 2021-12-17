<?php

namespace MelhorEnvio\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;

class ApiClient extends Client
{

    // Identificação do app
    protected $clientId;

    protected $secretKey;

    protected $nameApp;

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
        $this->nameApp = $config['nameApp'] ?? '';

        $config['handler'] = $stack;

        $config['headers'] = [
            'Accept' => 'application/json'
        ];

        parent::__construct($config);
    }

    public function request($method, $uri = '', array $options = [])
    {
        try {

            $options = array_merge($options, [
                'auth' => [
                    $this->clientId,
                    $this->secretKey
                ], ['headers' =>
                        [
                            "Authorization: Bearer {$this->accessToken}", "Content-Type: application/json",
                            "User-Agent: {$this->nomeApp} ({$this->emailTecnico})"
                        ]
                ]
            ]);

            return parent::request($method, $uri, $options);

        } catch (ClientException $e) {
            throw new ApiException(
                $e->getMessage(),
                $e->getCode(),
                $e->getResponse()
            );
        }
    }
}
