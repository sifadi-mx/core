<?php

declare(strict_types=1);

namespace Sifadi\Core\Exceptions;

use RuntimeException;

/**
 * Base class for every exception thrown by the SDK.
 *
 * Catch this to handle any Sifadi error at once; catch a subclass
 * ({@see ApiException}, {@see ConnectionException}) for finer control.
 */
class SifadiException extends RuntimeException {}
