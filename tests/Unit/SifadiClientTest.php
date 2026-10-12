<?php

declare(strict_types=1);

namespace Sifadi\Core\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Sifadi\Core\Exceptions\AuthenticationException;
use Sifadi\Core\Exceptions\InsufficientStampsException;
use Sifadi\Core\Exceptions\ValidationException;
use Sifadi\Core\SifadiClient;

final class SifadiClientTest extends TestCase
{
    /** @var array<int, array{request: Request}> */
    private array $history = [];

    /**
     * @param  array<int, GuzzleResponse>  $responses
     */
    private function client(array $responses): SifadiClient
    {
        $this->history = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        $guzzle = new Client(['handler' => $stack, 'http_errors' => false]);

        return new SifadiClient('tok_123', 'https://api.sifadi.test', ['client' => $guzzle]);
    }

    private function lastRequest(): Request
    {
        return $this->history[array_key_last($this->history)]['request'];
    }

    private function json(int $status, array $body): GuzzleResponse
    {
        return new GuzzleResponse($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    public function test_it_creates_and_stamps_an_invoice(): void
    {
        $sifadi = $this->client([
            $this->json(201, ['data' => ['id' => 987, 'uuid' => '3f2a9c14', 'estado' => 'timbrado', 'total' => '12298.32']]),
        ]);

        $cfdi = $sifadi->invoices()->create([
            'tipo_de_comprobante' => 'I',
            'emisor' => ['rfc' => 'EKU9003173C9'],
        ], 'idem-key-1');

        $this->assertSame(987, $cfdi['id']);
        $this->assertSame('timbrado', $cfdi['estado']);

        $request = $this->lastRequest();
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://api.sifadi.test/api/v1/invoices', (string) $request->getUri());
        $this->assertSame('Bearer tok_123', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('idem-key-1', $request->getHeaderLine('Idempotency-Key'));
        $this->assertSame('I', json_decode((string) $request->getBody(), true)['tipo_de_comprobante']);
    }

    public function test_create_draft_sets_timbrar_false(): void
    {
        $sifadi = $this->client([$this->json(201, ['data' => ['id' => 1, 'estado' => 'borrador']])]);

        $sifadi->invoices()->createDraft(['tipo_de_comprobante' => 'I']);

        $body = json_decode((string) $this->lastRequest()->getBody(), true);
        $this->assertFalse($body['opciones']['timbrar']);
    }

    public function test_cancel_sends_motivo_and_substitution(): void
    {
        $sifadi = $this->client([$this->json(200, ['data' => ['id' => 5, 'estado' => 'cancelado']])]);

        $sifadi->invoices()->cancel(5, '01', 'UUID-SUBS');

        $request = $this->lastRequest();
        $this->assertSame('https://api.sifadi.test/api/v1/invoices/5/cancel', (string) $request->getUri());
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('01', $body['motivo']);
        $this->assertSame('UUID-SUBS', $body['uuid_sustitucion']);
    }

    public function test_it_downloads_raw_xml(): void
    {
        $sifadi = $this->client([
            new GuzzleResponse(200, ['Content-Type' => 'application/xml'], '<cfdi:Comprobante/>'),
        ]);

        $xml = $sifadi->invoices()->xml(42);

        $this->assertSame('<cfdi:Comprobante/>', $xml);
        $this->assertSame('https://api.sifadi.test/api/v1/invoices/42/xml', (string) $this->lastRequest()->getUri());
    }

    public function test_it_lists_issuers(): void
    {
        $sifadi = $this->client([$this->json(200, ['data' => [['id' => 12, 'rfc' => 'EKU9003173C9']]])]);

        $issuers = $sifadi->issuers()->list();

        $this->assertCount(1, $issuers);
        $this->assertSame('EKU9003173C9', $issuers[0]['rfc']);
    }

    public function test_it_reads_the_stamp_balance(): void
    {
        $sifadi = $this->client([$this->json(200, ['available' => 163, 'included' => [], 'recharge' => []])]);

        $this->assertSame(163, $sifadi->stamps()->available());
    }

    public function test_422_throws_validation_exception_with_errors(): void
    {
        $sifadi = $this->client([
            $this->json(422, ['message' => 'Datos inválidos', 'errors' => ['metodo_pago' => ['requerido']]]),
        ]);

        try {
            $sifadi->invoices()->create(['tipo_de_comprobante' => 'I']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->status());
            $this->assertSame('Datos inválidos', $e->getMessage());
            $this->assertSame(['requerido'], $e->errors()['metodo_pago']);
        }
    }

    public function test_402_throws_insufficient_stamps_with_counts(): void
    {
        $sifadi = $this->client([
            $this->json(402, ['message' => 'Sin saldo', 'errors' => ['stamps' => ['available' => 0, 'required' => 1]]]),
        ]);

        try {
            $sifadi->invoices()->create(['tipo_de_comprobante' => 'I']);
            $this->fail('Expected InsufficientStampsException');
        } catch (InsufficientStampsException $e) {
            $this->assertSame(0, $e->available());
            $this->assertSame(1, $e->required());
        }
    }

    public function test_401_throws_authentication_exception(): void
    {
        $sifadi = $this->client([$this->json(401, ['message' => 'Unauthenticated.'])]);

        $this->expectException(AuthenticationException::class);
        $sifadi->stamps()->balance();
    }
}
