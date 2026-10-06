<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Option;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\Validator;

class QuestionsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected $examId;
    protected $currentRow = 0;

    public function __construct($examId)
    {
        $this->examId = $examId;
    }

    public function model(array $row)
    {
        $this->currentRow++;

        // Clean up the data
        $row = array_map('trim', $row);

        // Determine question type
        $type = strtolower($row['type'] ?? 'mcq');
        if (!in_array($type, ['mcq', 'true_false', 'short_answer'])) {
            $type = 'mcq';
        }

        // Get next order number
        $order = Question::where('exam_id', $this->examId)->max('order') + 1;

        // Create question
        $question = new Question([
            'exam_id' => $this->examId,
            'question_text' => $row['question'] ?? $row['question_text'] ?? '',
            'type' => $type,
            'marks' => floatval($row['marks'] ?? 1),
            'order' => $order,
            'is_reusable' => isset($row['reusable']) ? (bool)$row['reusable'] : false,
        ]);

        $question->save();

        // Create options based on type
        if ($type === 'mcq') {
            $correctOption = strtolower(trim($row['correct_answer'] ?? $row['correct_option'] ?? 'a'));
            $optionLetters = ['a', 'b', 'c', 'd', 'e'];

            foreach ($optionLetters as $letter) {
                $optionText = $row["option_{$letter}"] ?? $row[$letter] ?? null;
                if ($optionText && trim($optionText) !== '') {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => trim($optionText),
                        'is_correct' => $correctOption === $letter,
                        'label' => $letter
                    ]);
                }
            }

            // Validate that we have at least 2 options
            $optionCount = Option::where('question_id', $question->id)->count();
            if ($optionCount < 2) {
                $question->delete();
                throw new \Exception("Row {$this->currentRow}: MCQ questions must have at least 2 options");
            }

        } elseif ($type === 'true_false') {
            $correctOption = strtolower(trim($row['correct_answer'] ?? 'true'));
            $correctOption = in_array($correctOption, ['true', 'false']) ? $correctOption : 'true';

            Option::create([
                'question_id' => $question->id,
                'option_text' => 'True',
                'is_correct' => $correctOption === 'true',
                'label' => 'true'
            ]);

            Option::create([
                'question_id' => $question->id,
                'option_text' => 'False',
                'is_correct' => $correctOption === 'false',
                'label' => 'false'
            ]);

        } elseif ($type === 'short_answer') {
            $correctAnswer = $row['correct_answer'] ?? $row['answer'] ?? '';

            Option::create([
                'question_id' => $question->id,
                'option_text' => trim($correctAnswer),
                'is_correct' => true,
                'label' => 'answer'
            ]);
        }

        return $question;
    }

    public function rules(): array
    {
        return [
            'question' => 'required',
            'type' => 'nullable|in:mcq,true_false,short_answer',
            'marks' => 'nullable|numeric|min:0.1',
            'reusable' => 'nullable|boolean',
            'correct_answer' => 'nullable',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'question.required' => 'Question text is required',
            'type.in' => 'Type must be one of: mcq, true_false, short_answer',
        ];
    }
}
