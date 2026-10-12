<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 402 — the account has no prepaid stamp balance left ("no balance, no stamp").
 * Inspect {@see available()} / {@see required()} and prompt the user to recharge.
 */
final class InsufficientStampsException extends ApiException
{
    /** Stamps currently available on the account, if the API reported it. */
    public function available(): ?int
    {
        return $this->stampValue('available');
    }

    /** Stamps the failed request needed, if the API reported it. */
    public function required(): ?int
    {
        return $this->stampValue('required');
    }

    private function stampValue(string $key): ?int
    {
        $value = $this->response->get('errors.stamps.'.$key);

        return is_numeric($value) ? (int) $value : null;
    }
}
