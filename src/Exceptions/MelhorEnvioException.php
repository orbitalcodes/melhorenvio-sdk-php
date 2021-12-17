<?php

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class MelhorEnvioException extends Exception
{
    /**
     * @var array
     */
    protected $retorno;

    public function __construct(array $message, $code = 0, Throwable $previous = null)
    {
        $this->retorno = $message;

        parent::__construct($message['data'], $code, $previous);
    }
}
