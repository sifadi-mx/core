<?php

declare(strict_types=1);

namespace Sifadi\Core\Resources;

use Sifadi\Core\Http\HttpClient;

/**
 * Emisores (RFCs) of the connected account. Use this to check which RFCs can
 * stamp (their CSD is vigente) before spending a stamp.
 */
final class Issuers
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * All active issuers, ordered by name (GET /api/v1/issuers). Not paginated.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $data = $this->http->get('/api/v1/issuers')->get('data', []);

        return is_array($data) ? $data : [];
    }

    /**
     * A single issuer (GET /api/v1/issuers/{id}).
     *
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $data = $this->http->get("/api/v1/issuers/{$id}")->get('data', []);

        return is_array($data) ? $data : [];
    }
}
