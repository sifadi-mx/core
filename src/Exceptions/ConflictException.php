<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 409 — a conflicting state blocks the request, typically the issuer has no
 * valid/vigente CSD and therefore cannot stamp.
 */
final class ConflictException extends ApiException {}
