<?php

namespace Mvdnbrk\DhlParcel\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DhlParcelException extends Exception
{
    /** @var \GuzzleHttp\Psr7\Response */
    protected $response;

    /**
     * Create a new DhlParcelException instance.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  \GuzzleHttp\Psr7\Response|null  $response
     * @param  \Throwable|null  $previous
     */
    public function __construct(string $message = '', int $code = 0, ResponseInterface $response = null, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->response = $response;
    }

    /**
     *  Create a new DhlParcelException instance from the given Guzzle request exception.
     *
     * @param  \GuzzleHttp\Exception\RequestException  $exception
     * @return static
     */
    public static function createFromGuzzleRequestException(RequestException $exception)
    {
        return new static(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getResponse(),
            $exception
        );
    }

    /**
     * Create a new DhlParcelException instance from the given response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @param  \Throwable|null  $previous
     * @return static
     */
    public static function createFromResponse(ResponseInterface $response, Throwable $previous = null)
    {
        $object = static::parseResponseBody($response);

        $errorDetails = '';
        foreach ($object->details as $key => $value) {
            $errorDetails .= $key . ': ' . $value[0] . "\n";
        }

        return new static(
            'Error executing API call: '.$object->message. "\n" . $errorDetails,
            $response->getStatusCode(),
            $response,
            $previous
        );
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Parse the body of a response.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return object
     * @throws \Mvdnbrk\DhlParcel\Exceptions\DhlParcelException
     */
    protected static function parseResponseBody(ResponseInterface $response)
    {
        $body = (string) $response->getBody();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new static("Unable to decode DHL Parcel response: '{$body}'.");
        }

        return $object;
    }
}
