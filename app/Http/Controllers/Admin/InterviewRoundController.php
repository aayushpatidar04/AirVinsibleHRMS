<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewRound;
use App\Models\Question;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InterviewRoundController extends Controller
{
    private const QUESTION_TYPES = [
        'short_answer'    => 'Short Answer',
        'long_answer'     => 'Long Answer',
        'rating_scale'    => 'Rating Scale (1–5)',
        'yes_no'          => 'Yes / No',
        'multiple_choice' => 'Multiple Choice',
    ];

    public function index()
    {
        $rounds = InterviewRound::ordered()
            ->withCount('questions', 'candidateProgress')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'name'             => $r->name,
                'sequence'         => $r->sequence_number,
                'description'      => $r->description,
                'is_mandatory'     => $r->is_mandatory,
                'is_hr_round'      => $r->is_hr_round,
                'is_ops_round'     => $r->is_ops_round,
                'is_active'        => $r->is_active,
                'questions_count'  => $r->questions_count,
                'candidates_count' => $r->candidate_progress_count,
                'stats'            => $r->getStats(),
            ]);

        return Inertia::render('Admin/Rounds/Index', [
            'rounds' => $rounds,
        ]);
    }

    public function create()
    {
        $next = (InterviewRound::max('sequence_number') ?? 0) + 1;

        return Inertia::render('Admin/Rounds/Create', [
            'nextSequence' => $next,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:255|unique:interview_rounds,name',
            'sequence_number' => 'required|integer|min:1|unique:interview_rounds,sequence_number',
            'description'     => 'nullable|string|max:1000',
            'is_mandatory'    => 'boolean',
            'is_hr_round'     => 'boolean',
            'is_ops_round'    => 'boolean',
        ]);

        $round = InterviewRound::create(array_merge($data, [
            'slug'       => Str::slug($data['name']),
            'is_active'  => true,
            'created_by' => Auth::id(),
        ]));

        return redirect()
            ->route('admin.rounds.show', $round)
            ->with('success', 'Interview round created successfully.');
    }

    public function show(InterviewRound $round)
    {
        $questions = $round->questions()->get()->map(fn ($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
            'question_type' => $q->question_type,
            'is_mandatory'  => $q->is_mandatory,
            'is_custom'     => $q->is_custom,
            'order'         => $q->order,
            'options'       => $q->options,
            'score_category'=> $q->score_category,
        ]);

        $allInterviewers = User::query()
            ->active()
            ->canInterview()
            ->with('branch')
            ->get(['id', 'first_name', 'last_name', 'employee_id', 'branch_id']);

        $allowedInterviewerIds = $round->allowedInterviewers()->pluck('users.id')->toArray();

        return Inertia::render('Admin/Rounds/Show', [
            'round' => [
                'id'           => $round->id,
                'name'         => $round->name,
                'sequence'     => $round->sequence_number,
                'description'  => $round->description,
                'is_mandatory' => $round->is_mandatory,
                'is_hr_round'  => $round->is_hr_round,
                'is_ops_round' => $round->is_ops_round,
                'is_active'    => $round->is_active,
                'stats'        => $round->getStats(),
            ],
            'questions'     => $questions,
            'questionTypes' => self::QUESTION_TYPES,
            'allInterviewers' => $allInterviewers->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->full_name,
                'employee_id' => $u->employee_id,
                'branch' => $u->branch?->name,
                'assigned' => in_array($u->id, $allowedInterviewerIds),
            ]),
            'allowedInterviewerIds' => $allowedInterviewerIds,
            'scoreCategories' => Question::SCORE_CATEGORIES,
        ]);
    }

    public function edit(InterviewRound $round)
    {
        return Inertia::render('Admin/Rounds/Edit', [
            'round' => $round,
        ]);
    }

    public function update(Request $request, InterviewRound $round)
    {
        $data = $request->validate([
            'name'            => "required|string|max:255|unique:interview_rounds,name,{$round->id}",
            'sequence_number' => "required|integer|min:1|unique:interview_rounds,sequence_number,{$round->id}",
            'description'     => 'nullable|string|max:1000',
            'is_mandatory'    => 'boolean',
            'is_hr_round'     => 'boolean',
            'is_ops_round'    => 'boolean',
            'is_active'       => 'boolean',
        ]);

        $round->update($data);

        return redirect()
            ->route('admin.rounds.show', $round)
            ->with('success', 'Round updated.');
    }

    public function destroy(InterviewRound $round)
    {
        if ($round->candidateProgress()->exists()) {
            return back()->with('error', 'Cannot delete round with assigned candidates.');
        }

        $round->delete();

        return redirect()
            ->route('admin.rounds.index')
            ->with('success', 'Round deleted.');
    }

    /* ── Questions ── */

    public function storeQuestion(Request $request, InterviewRound $round)
    {
        $data = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:' . implode(',', array_keys(self::QUESTION_TYPES)),
            'is_mandatory'  => 'boolean',
            'order'         => 'required|integer|min:1',
            'options'       => 'nullable|array',
            'score_category' => ['nullable', Rule::in(array_keys(Question::SCORE_CATEGORIES))],
        ]);

        Question::create(array_merge($data, [
            'round_id'   => $round->id,
            'is_custom'  => false,
            'created_by' => Auth::id(),
        ]));

        return back()->with('success', 'Question added.');
    }

    public function updateQuestion(Request $request, InterviewRound $round, Question $question)
    {
        abort_unless($question->round_id === $round->id, 403);

        $data = $request->validate([
            'question_text' => 'required|string|max:1000',
            'question_type' => 'required|in:' . implode(',', array_keys(self::QUESTION_TYPES)),
            'is_mandatory'  => 'boolean',
            'order'         => 'required|integer|min:1',
            'options'       => 'nullable|array',
            'score_category' => ['nullable', Rule::in(array_keys(Question::SCORE_CATEGORIES))],
        ]);

        $question->update($data);

        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(InterviewRound $round, Question $question)
    {
        abort_unless($question->round_id === $round->id, 403);
        $question->delete();
        return back()->with('success', 'Question removed.');
    }

    public function updateAllowedInterviewers(Request $request, InterviewRound $round)
    {
        $data = $request->validate([
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $round->allowedInterviewers()->sync($data['user_ids'] ?? []);

        return back()->with('success', 'Allowed interviewers updated for this round.');
    }
}