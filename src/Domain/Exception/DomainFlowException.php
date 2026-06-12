<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

use RuntimeException;

/**
 * Base type for the business outcomes this domain reports by throwing: the
 * legitimate situations a customer can bring about that end an action early.
 *
 * These are expected flows, not bugs — the application catches this base and
 * maps each named outcome to a message at the boundary (ADR 0004). The base
 * extends RuntimeException and never SPL's DomainException, whose
 * LogicException ancestry would brand every business outcome a programming
 * error. Invariant violations stay on the SPL types, thrown inline.
 */
abstract class DomainFlowException extends RuntimeException
{
}
