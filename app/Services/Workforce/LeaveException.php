<?php

namespace App\Services\Workforce;

use DomainException;

/** A leave action that breaks a policy rule; the message is shown to the user. */
class LeaveException extends DomainException
{
}
