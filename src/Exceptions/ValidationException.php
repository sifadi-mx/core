<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 422 — the payload failed validation (missing/invalid fields, SAT-aware
 * messages). Use {@see ApiException::errors()} for the per-field details.
 */
final class ValidationException extends ApiException {}
