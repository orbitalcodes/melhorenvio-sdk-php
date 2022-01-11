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

    public function __construct($message = "", Response $response = null, int $code = 0)
    {
        $this->response = $response;
        $message = $this->prepareMessage($message);

        parent::__construct($message, $response ? $response->getStatusCode() : $code);
    }

    protected function prepareMessage($message): string
    {
        $responseContent = $this->response->getResponse();
        if (property_exists($responseContent, 'errors') && $responseContent->errors) {
            foreach ((array)$responseContent->errors as $field => $errors) {
                $this->errors[$field] = is_array($errors) ? $errors : [$errors];
            }
        }

        $message = $responseContent->error ?? $message;
        $message = $responseContent->message ?? $message;

        return trim(explode('response:', $message)[1] ?? $message, PHP_EOL);
    }

    /**
     * Get the HTTP response header
     *
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsMessage(): ?string
    {
        $errorsResult = '';
        foreach ($this->errors as $field => $errors) {
            $errorsResult .= $field . ': ' . implode(', ', $errors);
        }

        return $errorsResult;
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
