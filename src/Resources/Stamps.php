<?php

declare(strict_types=1);

namespace Sifadi\Core\Resources;

use Sifadi\Core\Http\HttpClient;

/**
 * Timbres (stamps) — the account's prepaid balance.
 */
final class Stamps
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Current stamp balance (GET /api/v1/stamps/balance). Read-only and safe to poll.
     *
     * Check `available` before stamping ("no balance, no stamp").
     *
     * @return array{available: int, included: array<string, mixed>, recharge: array<string, mixed>}
     */
    public function balance(): array
    {
        /** @var array{available: int, included: array<string, mixed>, recharge: array<string, mixed>} $json */
        $json = $this->http->get('/api/v1/stamps/balance')->json();

        return $json;
    }

    /**
     * Just the number of stamps usable right now.
     */
    public function available(): int
    {
        return (int) ($this->balance()['available'] ?? 0);
    }
}
