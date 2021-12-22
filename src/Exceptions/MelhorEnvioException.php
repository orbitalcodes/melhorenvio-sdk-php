<?php

namespace MelhorEnvio\Exceptions;

use \Exception;
use MelhorEnvio\Client\Response;

class MelhorEnvioException extends Exception
{
    /**
     * The server response.
     *
     * @var Response
     */
    protected $response;

    protected $errors = [];

    public function __construct($message = "", int $code = 0, Response $response)
    {
        $this->response = $response;
        $message = $this->prepareMessage($response, $message);

        parent::__construct($message, $code);
    }

    protected function prepareMessage($response, $message): string
    {
        $message = $this->response->getResponse()->error ?? $message;
        $message = $this->response->getResponse()->message ?? $message;

        return trim(explode('response:', $message)[1] ?? $message, PHP_EOL);
    }

    /**
     * Get the HTTP response header
     *
     * @return string HTTP response header
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function getErrorMessageCode(): string
    {
        return $this->getErrorMessageByCode($this->getCode());
    }

    /**
     * Retorna o response ja tratado
     *
     * @param $code
     * @return string
     */
    public function getErrorMessageByCode($code): string
    {
        switch ($code) {
            case 400:
            {
                return 'Requisição Mal Formada';
            }
            case 401:
            {
                return 'Usuário não autorizado';
            }
            case 403:
            {
                return 'Acesso não autorizado';
            }
            case 404:
            {
                return 'Recurso não Encontrado';
            }
            case 405:
            {
                return 'Operação não suportada';
            }
            case 408:
            {
                return 'Tempo esgotado para a requisição';
            }
            case 409:
            {
                return 'Recurso em conflito';
            }
            case 413:
            {
                return 'Requisição excede o tamanho máximo permitido';
            }
            case 415:
            {
                return 'Content-type inválido';
            }
            case 422:
            {
                return 'Não foi possível processar as instruções contidas na requisição';
            }
            case 429:
            {
                return 'Requisição excede a quantidade máxima de chamadas permitidas à API.';
            }
            case 500:
            {
                return 'Erro na API';
            }
        }
    }
}
