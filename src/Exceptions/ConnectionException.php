<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

/**
 * The request never reached the Sifadi API (DNS, timeout, TLS, connection
 * refused). No HTTP response was received, so there is no status to inspect.
 */
final class ConnectionException extends SifadiException {}
