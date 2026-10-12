<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

use Sifadi\Core\Http\Response;

/**
 * The Sifadi API returned an error status (>= 400). The original
 * {@see Response} is attached so callers can inspect the status, the
 * human message and any per-field validation errors.
 */
class ApiException extends SifadiException
{
    public function __construct(
        string $message,
        public readonly Response $response,
    ) {
        parent::__construct($message, $response->status);
    }

    /** The HTTP status code returned by the API. */
    public function status(): int
    {
        return $this->response->status;
    }

    /**
     * Per-field validation errors (`{ "field": ["..."] }`), present on 422.
     *
     * @return array<string, mixed>
     */
    public function errors(): array
    {
        $errors = $this->response->get('errors');

        return is_array($errors) ? $errors : [];
    }

    /**
     * Build the right exception subclass for a failed response.
     */
    public static function fromResponse(Response $response): self
    {
        $message = $response->get('message');
        $message = is_string($message) && $message !== ''
            ? $message
            : 'Sifadi API error (HTTP '.$response->status.')';

        return match ($response->status) {
            401 => new AuthenticationException($message, $response),
            402 => new InsufficientStampsException($message, $response),
            403 => new AuthorizationException($message, $response),
            404 => new NotFoundException($message, $response),
            409 => new ConflictException($message, $response),
            422 => new ValidationException($message, $response),
            429 => new RateLimitException($message, $response),
            502 => new PacException($message, $response),
            default => new self($message, $response),
        };
    }
}
