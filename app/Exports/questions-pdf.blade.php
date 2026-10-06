<!DOCTYPE html>
<html>
<head>
    <title>Questions Export</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .question { margin-bottom: 20px; }
        .question-text { font-weight: bold; margin-bottom: 10px; }
        .option { margin-left: 20px; }
        .correct { color: green; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .meta { font-size: 12px; color: #666; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Questions Export</h1>
        <p>Generated on {{ date('Y-m-d H:i:s') }}</p>
    </div>

    @foreach($questions as $index => $question)
        <div class="question">
            <div class="meta">
                Question {{ $index + 1 }} |
                Type: {{ ucfirst($question->type) }} |
                Difficulty: {{ ucfirst($question->difficulty ?? 'Not set') }}
            </div>
            <div class="question-text">{!! $question->question_text !!}</div>

            @if($question->type === 'mcq')
                @foreach($question->options as $option)
                    <div class="option {{ $option->is_correct ? 'correct' : '' }}">
                        {{ strtoupper($option->label) }}. {{ $option->option_text }}
                        @if($option->is_correct) ✓ @endif
                    </div>
                @endforeach
            @elseif($question->type === 'true_false')
                @foreach($question->options as $option)
                    <div class="option {{ $option->is_correct ? 'correct' : '' }}">
                        {{ $option->option_text }}
                        @if($option->is_correct) ✓ @endif
                    </div>
                @endforeach
            @elseif($question->type === 'short_answer')
                <div class="option correct">
                    Correct Answer: {{ $question->options->first()->option_text ?? '' }}
                </div>
            @endif
        </div>
    @endforeach
</body>
</html>
