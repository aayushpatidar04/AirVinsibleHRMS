<?php

namespace App\Services\Workforce;

use DomainException;

/** A payroll action that is not allowed right now; the message is shown to the user. */
class PayrollException extends DomainException
{
}
