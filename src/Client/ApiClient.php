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

    protected $refreshToken;

    protected $tokenValidate;

    protected $onTokenRefresh;

    protected $refreshAttempts = 0;

    protected $isRefreshing = false;

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
        $this->refreshToken = $config['refreshToken'] ?? '';
        $this->tokenValidate = $config['tokenValidate'] ?? '';
        $this->email = $config['email'] ?? '';
        $this->appName = $config['appName'] ?? '';
        $this->onTokenRefresh = $config['onTokenRefresh'] ?? null;

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

    public function setOnTokenRefresh(callable $callback)
    {
        $this->onTokenRefresh = $callback;
        return $this;
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        try {
            if ($this->accessToken) {
                $options['headers']['Authorization'] = "Bearer {$this->accessToken}";
            }

            return parent::request($method, $uri, $options);

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 401 && 
                $this->refreshToken && 
                !$this->isRefreshing && 
                $this->refreshAttempts < 2) {
                
                $this->isRefreshing = true;
                $this->refreshAttempts++;

                if ($this->onTokenRefresh) {
                    $newTokens = call_user_func($this->onTokenRefresh, $this->refreshToken);
                    if ($newTokens) {
                        $this->accessToken = $newTokens['accessToken'];
                        $this->refreshToken = $newTokens['refreshToken'];
                        $this->tokenValidate = $newTokens['tokenValidate'];
                        
                        $options['headers']['Authorization'] = "Bearer {$this->accessToken}";
                        
                        $this->isRefreshing = false;
                        return parent::request($method, $uri, $options);
                    }
                }
                
                $this->isRefreshing = false;
            }
            
            throw new MelhorEnvioException(
                $e->getMessage(),
                $e->getResponse(),
                $e->getCode()
            );
        }
    }
}
