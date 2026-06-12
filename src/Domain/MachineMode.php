<?php

declare(strict_types=1);

namespace Vending\Domain;

/**
 * The two ways the machine can be open: selling to customers, or opened by a
 * technician for service. Customer and service actions are mutually exclusive;
 * the aggregate checks this mode before acting and refuses the wrong side as
 * a domain flow — nothing stops a customer pressing a button while the machine
 * stands open, so the machine answers, it does not break.
 */
enum MachineMode
{
    case Selling;
    case Service;
}
