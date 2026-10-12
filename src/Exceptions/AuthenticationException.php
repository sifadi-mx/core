<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 401 — the access token is missing, invalid or expired. Re-run the connect
 * flow (OAuth) or check the API key.
 */
final class AuthenticationException extends ApiException {}
