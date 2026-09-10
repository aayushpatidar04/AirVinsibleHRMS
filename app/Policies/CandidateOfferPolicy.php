<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\CandidateOffer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class CandidateOfferPolicy
{
    use HandlesAuthorization;

    /**
     * System administrators may perform every offer action.
     *
     * Adjust these checks according to your User model.
     */
    public function before(User $user, string $ability): bool|null
    {
        /*
         * Keep only the checks supported by your project.
         *
         * Examples:
         * $user->is_admin
         * $user->role === 'admin'
         * $user->hasRole('Super Admin')
         */

        if (
            method_exists($user, 'hasRole') &&
            $user->hasRole('Super Admin')
        ) {
            return true;
        }

        if (
            property_exists($user, 'is_admin') &&
            $user->is_admin
        ) {
            return true;
        }

        return null;
    }

    /**
     * View the complete offer listing.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasOfferManagementAccess($user);
    }

    /**
     * View a particular candidate offer.
     */
    public function view(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        return $this->canManageCandidate(
            $user,
            $candidateOffer->candidate
        );
    }

    /**
     * Create an offer for a selected candidate.
     */
    public function create(
        User $user,
        Candidate $candidate
    ): bool {
        if (!$this->canManageCandidate($user, $candidate)) {
            return false;
        }

        /*
         * An offer should only be created once the final hiring
         * decision is Selected.
         */
        return $candidate->final_status === 'selected';
    }

    /**
     * Edit an existing offer.
     */
    public function update(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return $candidateOffer->can_edit;
    }

    /**
     * Submit a draft for approval.
     */
    public function submitForApproval(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return $candidateOffer->can_submit_for_approval;
    }

    /**
     * Approve an offer.
     *
     * You can later restrict this specifically to HR Heads,
     * managers or administrators.
     */
    public function approve(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canApproveOffers($user)
        ) {
            return false;
        }

        return $candidateOffer->can_approve;
    }

    /**
     * Generate the final PDF.
     *
     * The offer must be approved first.
     */
    public function generatePdf(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return in_array(
            $candidateOffer->status,
            [
                CandidateOffer::STATUS_APPROVED,
                CandidateOffer::STATUS_SENT,
                CandidateOffer::STATUS_VIEWED,
            ],
            true
        );
    }

    /**
     * Send an approved offer to the candidate.
     */
    public function send(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return $candidateOffer->can_send;
    }

    /**
     * Cancel an offer that has not already been closed.
     */
    public function cancel(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return $candidateOffer->can_cancel;
    }

    /**
     * Create a revised version of an existing offer.
     */
    public function createRevision(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        /*
         * Do not revise an accepted or cancelled offer directly.
         */
        return !in_array(
            $candidateOffer->status,
            [
                CandidateOffer::STATUS_ACCEPTED,
                CandidateOffer::STATUS_CANCELLED,
            ],
            true
        );
    }

    /**
     * Delete a draft offer.
     */
    public function delete(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        if (
            !$this->canManageCandidate(
                $user,
                $candidateOffer->candidate
            )
        ) {
            return false;
        }

        return $candidateOffer->status ===
            CandidateOffer::STATUS_DRAFT;
    }

    /**
     * Restore a soft-deleted draft offer.
     */
    public function restore(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        return $this->canManageCandidate(
            $user,
            $candidateOffer->candidate
        );
    }

    /**
     * Permanently deleting an offer should not be allowed
     * through the application UI.
     */
    public function forceDelete(
        User $user,
        CandidateOffer $candidateOffer
    ): bool {
        return false;
    }

    /**
     * Reuse the existing Candidate policy's finalDecision ability.
     */
    private function canManageCandidate(
        User $user,
        Candidate $candidate
    ): bool {
        return Gate::forUser($user)->allows(
            'finalDecision',
            $candidate
        );
    }

    /**
     * General access for the offer listing.
     */
    private function hasOfferManagementAccess(
        User $user
    ): bool {
        /*
         * Spatie Permission support.
         */
        if (
            method_exists($user, 'can') &&
            $user->can('manage candidate offers')
        ) {
            return true;
        }

        /*
         * Role fallback.
         */
        if (
            method_exists($user, 'hasAnyRole') &&
            $user->hasAnyRole([
                'Super Admin',
                'Admin',
                'HR',
                'HR Manager',
            ])
        ) {
            return true;
        }

        /*
         * Replace this fallback with your own role logic
         * when neither Spatie permissions nor roles are used.
         */
        return false;
    }

    /**
     * Approval can be more restrictive than normal editing.
     */
    private function canApproveOffers(
        User $user
    ): bool {
        if (
            method_exists($user, 'can') &&
            $user->can('approve candidate offers')
        ) {
            return true;
        }

        if (
            method_exists($user, 'hasAnyRole') &&
            $user->hasAnyRole([
                'Super Admin',
                'Admin',
                'HR Manager',
            ])
        ) {
            return true;
        }

        return false;
    }
}