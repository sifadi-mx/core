<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 429 — too many requests. Back off and retry after {@see retryAfter()} seconds.
 */
final class RateLimitException extends ApiException
{
    /** Seconds to wait before retrying, from the body or the Retry-After header. */
    public function retryAfter(): ?int
    {
        $value = $this->response->get('retry_after')
            ?? $this->response->header('Retry-After');

        return is_numeric($value) ? (int) $value : null;
    }
}
