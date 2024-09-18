<?php

namespace Orwallet\FoxtrotSdk\Exception;

use Exception;
use Illuminate\Http\Client\Response;

class FoxtrotFailedResponseException extends Exception
{
    public $request;

    public $response;

    public $message;

    public $errors = [];

    private $fixable;

    public function __construct(
        array $request,
        Response $response,
        string $message,
        array $errors = [],
        bool $fixable = false
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->message = $message;
        $this->errors = $errors;
        $this->fixable = $fixable;
    }

    public function isFixable(): bool
    {
        return $this->fixable;
    }
}
