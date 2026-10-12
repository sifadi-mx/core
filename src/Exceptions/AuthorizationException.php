<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 403 — the token lacks the required scope (e.g. `invoices:write`) or the
 * action is not allowed in the resource's current state.
 */
final class AuthorizationException extends ApiException {}
