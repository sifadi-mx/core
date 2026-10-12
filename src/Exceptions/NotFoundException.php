<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * 404 — the resource does not exist in the account (e.g. the `emisor.rfc` is
 * not a registered issuer, or an unknown invoice id).
 */
final class NotFoundException extends ApiException {}
