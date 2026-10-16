<?php

namespace Tests\Unit;

use App\Mail\CandidateOfferMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\TestCase;

class CandidateOfferMailTest extends TestCase
{
    public function test_candidate_offer_mail_is_sent_synchronously(): void
    {
        $this->assertFalse(
            is_subclass_of(
                CandidateOfferMail::class,
                ShouldQueue::class
            )
        );
    }
}
