<?php

declare(strict_types=1);

namespace Sifadi\Core;

use GuzzleHttp\Client;
use Sifadi\Core\Http\HttpClient;
use Sifadi\Core\Resources\Invoices;
use Sifadi\Core\Resources\Issuers;
use Sifadi\Core\Resources\Stamps;

/**
 * Entry point to the Sifadi API.
 *
 * ```php
 * $sifadi = new SifadiClient('ACCESS_TOKEN', 'https://app.sifadi.mx');
 *
 * $cfdi = $sifadi->invoices()->create([...]);   // create + stamp
 * $xml  = $sifadi->invoices()->xml($cfdi['id']);
 * $saldo = $sifadi->stamps()->balance();
 * ```
 *
 * The token is an OAuth access token ("Connect with Sifadi") or a personal
 * API key — this SDK only sends it as `Authorization: Bearer`; obtaining it is
 * the host application's job.
 */
final class SifadiClient
{
    public const DEFAULT_BASE_URL = 'https://app.sifadi.mx';

    private readonly HttpClient $http;

    /**
     * @param  string  $token  OAuth access token or personal API key.
     * @param  string  $baseUrl  API base URL (no trailing /api/v1).
     * @param  array{client?: Client, timeout?: int, user_agent?: string}  $options
     */
    public function __construct(
        string $token,
        string $baseUrl = self::DEFAULT_BASE_URL,
        array $options = [],
    ) {
        $this->http = new HttpClient($token, rtrim($baseUrl, '/'), $options);
    }

    /** CFDI: create & stamp, sign, cancel, download XML/PDF. */
    public function invoices(): Invoices
    {
        return new Invoices($this->http);
    }

    /** Emisores (RFCs) of the account. */
    public function issuers(): Issuers
    {
        return new Issuers($this->http);
    }

    /** Prepaid stamp balance. */
    public function stamps(): Stamps
    {
        return new Stamps($this->http);
    }

    /**
     * Identity + plan of the token (GET /api/v1/me).
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->http->get('/api/v1/me')->json();
    }

    /** The underlying transport, for advanced/raw calls. */
    public function http(): HttpClient
    {
        return $this->http;
    }
}
