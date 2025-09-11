<?php

namespace AuthSystem\Core\Exception;

use AuthSystem\Core\Http\Response;
use AuthSystem\Core\Logger\Logger;
use AuthSystem\Core\Config\Config;


class Handler
{
    private Logger $logger;
    private Config $config;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->config = new Config();
    }


    public function handle(\Throwable $e): Response
    {

        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);


        if ($e instanceof ValidationException) {
            return Response::validationError($e->getErrors());
        }

        if ($e instanceof AuthenticationException) {
            return Response::unauthorized($e->getMessage());
        }

        if ($e instanceof AuthorizationException) {
            return Response::forbidden($e->getMessage());
        }

        if ($e instanceof NotFoundException) {
            return Response::notFound($e->getMessage());
        }

        if ($e instanceof MethodNotAllowedException) {
            return Response::methodNotAllowed($e->getMessage());
        }

        if ($e instanceof TooManyRequestsException) {
            return Response::tooManyRequests($e->getMessage());
        }


        if ($this->config->get('app.debug', false)) {
            return Response::error($e->getMessage(), 500);
        }

        return Response::error('Internal Server Error', 500);
    }
}


class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}


class AuthenticationException extends \Exception
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message);
    }
}


class AuthorizationException extends \Exception
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message);
    }
}


class NotFoundException extends \Exception
{
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message);
    }
}


class MethodNotAllowedException extends \Exception
{
    public function __construct(string $message = 'Method not allowed')
    {
        parent::__construct($message);
    }
}


class TooManyRequestsException extends \Exception
{
    public function __construct(string $message = 'Too many requests')
    {
        parent::__construct($message);
    }
}