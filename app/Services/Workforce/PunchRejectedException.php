<?php

namespace App\Services\Workforce;

use DomainException;

/** A punch that was refused; the message is safe to show to the employee. */
class PunchRejectedException extends DomainException
{
}
