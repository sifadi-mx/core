<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 502 — the upstream PAC/SAT rejected or failed the stamp or cancellation.
 * The PAC's own code/message is passed through in {@see ApiException::getMessage()}.
 * Transient failures are safe to retry (reuse the same Idempotency-Key when stamping).
 */
final class PacException extends ApiException {}
