<?php

declare(strict_types=1);

namespace Sifadi\Core\Http;

/**
 * A thin, framework-agnostic wrapper over a raw HTTP response from the Sifadi API.
 *
 * It exposes the status code, the raw body (for XML/PDF downloads) and a lazily
 * decoded JSON array (for the regular JSON endpoints), plus header access.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers;

    private ?array $decoded = null;

    /**
     * @param  array<string, string>  $headers  Header name (lowercased) => first value.
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        array $headers = [],
    ) {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower((string) $name)] = $value;
        }
        $this->headers = $normalized;
    }

    /** Whether the status code is in the 2xx range. */
    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * The decoded JSON body as an associative array (empty array when not JSON).
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->decoded === null) {
            $decoded = json_decode($this->body, true);
            $this->decoded = is_array($decoded) ? $decoded : [];
        }

        return $this->decoded;
    }

    /** A value from the decoded JSON body using dot notation, or $default. */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->json();
        foreach (explode('.', $key) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /** A single response header (case-insensitive), or null. */
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
