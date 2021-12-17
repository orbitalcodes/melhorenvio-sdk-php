<?php

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class EtiquetaException extends MelhorEnvoException
{
    /**
     * @var string
     */
    protected $etiquetaId;

    public function __construct(array $message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
