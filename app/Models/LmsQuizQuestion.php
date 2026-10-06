<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsQuizQuestion extends Model
{
    protected $table = 'lms_quiz_questions';

    public const TYPES = [
        'single'       => 'Single choice',
        'multiple'     => 'Multiple choice',
        'boolean'      => 'True / False',
        'short_answer' => 'Short answer',
        'fill_blank'   => 'Fill in the blank',
        'essay'        => 'Essay (manual grade)',
    ];

    /** Types the system grades automatically. */
    public const AUTO_TYPES = ['single', 'multiple', 'boolean', 'short_answer', 'fill_blank'];

    protected $fillable = [
        'quiz_id', 'question', 'type', 'options', 'correct', 'accepted_answers',
        'explanation', 'image_path', 'points', 'position',
    ];

    protected $casts = [
        'options'          => 'array',
        'correct'          => 'array',
        'accepted_answers' => 'array',
    ];

    public function quiz() { return $this->belongsTo(LmsQuiz::class, 'quiz_id'); }

    public function isChoice(): bool
    {
        return in_array($this->type, ['single', 'multiple', 'boolean'], true);
    }

    public function isManual(): bool
    {
        return $this->type === 'essay';
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . ltrim($this->image_path, '/')) : null;
    }

    /** True when the given selected option indexes exactly match the key. */
    public function isCorrect(array $selected): bool
    {
        $key = array_map('intval', (array) ($this->correct ?? []));
        $sel = array_values(array_unique(array_map('intval', $selected)));
        sort($key);
        sort($sel);
        return $key === $sel;
    }

    /**
     * Award points for a student's answer.
     *  - choice: full marks for an exact match; with $partial, proportional
     *    credit for multiple (right picks minus wrong picks, floored at 0).
     *  - short_answer / fill_blank: full marks if the trimmed, case-insensitive
     *    text matches any accepted answer.
     *  - essay: always 0 here (graded manually later).
     * Returns a float in [0, points].
     */
    public function award($answer, bool $partial = false): float
    {
        $pts = (float) $this->points;

        if ($this->type === 'essay') return 0.0;

        if ($this->isChoice()) {
            $sel = array_values(array_unique(array_map('intval', (array) $answer)));
            if ($this->isCorrect($sel)) return $pts;
            if ($partial && $this->type === 'multiple') {
                $key = array_map('intval', (array) ($this->correct ?? []));
                if (!$key) return 0.0;
                $right = count(array_intersect($sel, $key));
                $wrong = count(array_diff($sel, $key));
                $frac = max(0, ($right - $wrong)) / count($key);
                return round($pts * $frac, 2);
            }
            return 0.0;
        }

        // text answer
        $given = is_array($answer) ? implode(' ', $answer) : (string) $answer;
        $given = mb_strtolower(trim($given));
        if ($given === '') return 0.0;
        foreach ((array) ($this->accepted_answers ?? []) as $acc) {
            if (mb_strtolower(trim((string) $acc)) === $given) return $pts;
        }
        return 0.0;
    }
}
