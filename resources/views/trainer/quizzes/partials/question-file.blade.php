@php
    $inputId = $inputId ?? 'question-file';
    $templateUrl = $templateUrl ?? route('trainer.quizzes.import-template');
@endphp

<div class="lms-field lms-field-wide">
    <label for="{{ $inputId }}">Import questions from a file</label>
    <input id="{{ $inputId }}" type="file" name="question_file" accept=".txt,.text,.csv,.json,text/plain,text/csv,application/json">
    <p class="lms-muted-note">Optional. Use a .txt, .csv, or .json file. In a text file, leave a blank line between questions and mark the correct choice with * or <code>Answer: B</code>. <a href="{{ $templateUrl }}">Download a sample text file</a>.</p>
    @error('question_file')<p class="lms-field-error">{{ $message }}</p>@enderror
</div>
