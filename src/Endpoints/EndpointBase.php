<?php

namespace MelhorEnvio\Endpoints;

use MelhorEnvio\Client\ApiClient;
use MelhorEnvio\Client\Response;

abstract class EndpointBase
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    // Identificação do app
    protected $clientId;

    protected $secretKey;

    protected $nameApp;

    protected $email; // Email tecnico

    protected $accessToken;

    // Constantes
    protected $urlRastreio = "https://www.melhorrastreio.com.br/rastreio/";

    // Url de retorno da plataforma
    protected $urlCallback;

    /**
     * Debug switch (default set to false)
     */
    protected $sandbox = false;

    protected $urlProduction = 'https://www.melhorenvio.com.br/';

    protected $urlSandbox = 'https://sandbox.melhorenvio.com.br/';

    protected function getApiClient()
    {
        if ($this->apiClient === null) {

            $this->apiClient = new ApiClient([
                'client_id'   => $this->clientId,
                'secret_key'  => $this->secretKey,
                'appName'     => $this->appName,
                'email'       => $this->email,
                'accessToken' => $this->accessToken,
                'base_uri'    => self::$sandbox ? self::$urlSandbox : self::$urlProduction,
            ]);
        }

        return $this->apiClient;
    }

    /**
     * @param ApiClient $apiClient
     */
    public function setApiClient(ApiClient $apiClient): void
    {
        $this->apiClient = $apiClient;
    }

    public function request(string $method, string $uri, array $options = []): Response
    {
        return $this->getApiClient()->request($method, $uri, $options);
    }

    public function setClientId($clientId)
    {
        // Salva a informação na constante
        $this->clientId = $clientId;
        return $this;
    }

    public function setSecretKey($secretKey)
    {
        // Salva a informação na constante
        $this->secretKey = $secretKey;
        return $this;
    }

    public function setNameApp($nameApp)
    {
        // Salva a informação na constante
        $this->nameApp = $nameApp;
        return $this;
    }

    public function setEmail($email)
    {
        // Salva a informação na constante
        $this->email = $email;
        return $this;
    }

    /**
     * Método responsável por receber a url de retorno da
     * plataforma e salva na constante especifica.
     *
     * @param $url
     */
    public function setCallbackURL($url)
    {
        // Salva a informação na constante
        $this->urlCallback = $url;
        return $this;
    }

    /**
     * Método responsável por salvar o Access Token
     * na constante.
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        // Salva o Access Token
        $this->accessToken = $token;
        return $this;
    }
}
