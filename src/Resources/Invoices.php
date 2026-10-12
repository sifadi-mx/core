<?php

declare(strict_types=1);

namespace Sifadi\Core\Resources;

use Sifadi\Core\Http\HttpClient;

/**
 * CFDI (facturas) — create & stamp, sign, cancel, and download XML/PDF.
 *
 * @see https://sifadi.mx  Public CFDI Creation API (document shape, Spanish/SAT keys).
 */
final class Invoices
{
    public function __construct(private readonly HttpClient $http) {}

    /**
     * Create and stamp a CFDI in one call (POST /api/v1/invoices).
     *
     * `$document` is the Spanish/SAT "document shape" (serie, folio,
     * tipo_de_comprobante, emisor.rfc, receptor, conceptos[], ...). Sifadi
     * computes importes, subtotal, el bloque de impuestos y total — do not send them.
     * `opciones.timbrar` defaults to true; pass an idempotency key to make
     * "Timbrar" retries safe.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, mixed> The stamped CFDI (`data`): id, uuid, estado, total, links.xml/pdf...
     */
    public function create(array $document, ?string $idempotencyKey = null): array
    {
        $headers = $idempotencyKey !== null ? ['Idempotency-Key' => $idempotencyKey] : [];

        return $this->data($this->http->post('/api/v1/invoices', $document, $headers)->json());
    }

    /**
     * Create the CFDI as a draft only (opciones.timbrar = false), without stamping.
     *
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    public function createDraft(array $document, ?string $idempotencyKey = null): array
    {
        $document['opciones'] = ['timbrar' => false] + (array) ($document['opciones'] ?? []);

        return $this->create($document, $idempotencyKey);
    }

    /**
     * Sign a draft with the issuer's CSD (POST /api/v1/invoices/{id}/sign).
     *
     * @return array<string, mixed>
     */
    public function sign(int $id): array
    {
        return $this->data($this->http->post("/api/v1/invoices/{$id}/sign")->json());
    }

    /**
     * Stamp a signed CFDI at the PAC (POST /api/v1/invoices/{id}/stamp).
     *
     * @return array<string, mixed>
     */
    public function stamp(int $id): array
    {
        return $this->data($this->http->post("/api/v1/invoices/{$id}/stamp")->json());
    }

    /**
     * Cancel a stamped CFDI (POST /api/v1/invoices/{id}/cancel).
     *
     * @param  string  $motivo  SAT code: 01|02|03|04 (01 requires $uuidSustitucion).
     * @return array<string, mixed>
     */
    public function cancel(int $id, string $motivo, ?string $uuidSustitucion = null): array
    {
        $body = ['motivo' => $motivo];
        if ($uuidSustitucion !== null) {
            $body['uuid_sustitucion'] = $uuidSustitucion;
        }

        return $this->data($this->http->post("/api/v1/invoices/{$id}/cancel", $body)->json());
    }

    /**
     * Stamp details (GET /api/v1/invoices/{id}/stamp-info): uuid, sello_sat,
     * no_certificado_sat, fecha_timbrado, qr_code.
     *
     * @return array<string, mixed>
     */
    public function stampInfo(int $id): array
    {
        return $this->http->get("/api/v1/invoices/{$id}/stamp-info")->json();
    }

    /** Download the raw stamped XML (GET /api/v1/invoices/{id}/xml). */
    public function xml(int $id): string
    {
        return $this->http->get("/api/v1/invoices/{$id}/xml")->body;
    }

    /** Download the PDF bytes (GET /api/v1/invoices/{id}/pdf). */
    public function pdf(int $id): string
    {
        return $this->http->get("/api/v1/invoices/{$id}/pdf")->body;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function data(array $json): array
    {
        $data = $json['data'] ?? $json;

        return is_array($data) ? $data : [];
    }
}
