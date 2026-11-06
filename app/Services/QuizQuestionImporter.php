<?php

namespace App\Services;

use App\Models\QuizQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class QuizQuestionImporter
{
    public const MAX_QUESTIONS = 50;

    private string $errorKey = 'file';

    private bool $collectRowErrors = false;

    /** @var list<string> */
    private array $rowErrors = [];

    /**
     * @return list<array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float, position: int}>
     */
    public function import(UploadedFile $file, string $errorKey = 'file'): array
    {
        $this->errorKey = $errorKey;
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['txt', 'text', 'csv', 'json'], true)) {
            $this->fail('Upload a .txt, .csv, or .json file.');
        }

        $contents = $file->getContent();
        if (! is_string($contents) || trim($contents) === '') {
            $this->fail('The file is empty.');
        }

        if (str_contains($contents, "\0")) {
            $this->fail('Upload a plain text, CSV, or JSON file.');
        }

        $contents = $this->toUtf8($contents);
        $questions = match ($extension) {
            'json' => $this->fromJson($contents),
            'csv' => $this->fromCsv($contents),
            default => $this->fromText($contents),
        };

        if ($questions === []) {
            if ($this->rowErrors !== []) {
                $this->fail($this->rowErrors[0]);
            }

            $this->fail('No questions were found. Use the sample file and try again.');
        }

        if (count($questions) > self::MAX_QUESTIONS) {
            $this->fail('A quiz can have at most '.self::MAX_QUESTIONS.' questions.');
        }

        foreach ($questions as $index => &$question) {
            $question['position'] = $index;
        }
        unset($question);

        $seen = [];
        foreach ($questions as $question) {
            $prompt = mb_strtolower(trim($question['prompt']));
            if ($prompt === '') {
                continue;
            }

            if (isset($seen[$prompt])) {
                $this->rowProblem('Duplicate question: '.$question['prompt']);

                continue;
            }

            $seen[$prompt] = true;
        }

        return $questions;
    }

    /**
     * @return array{questions: list<array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float, position: int}>, errors: list<string>}
     */
    public function preview(UploadedFile $file, string $errorKey = 'file'): array
    {
        $this->collectRowErrors = true;
        $this->rowErrors = [];

        try {
            $questions = $this->import($file, $errorKey);
        } catch (ValidationException $exception) {
            if ($this->rowErrors === []) {
                throw $exception;
            }

            $questions = [];
        } finally {
            $errors = $this->rowErrors;
            $this->collectRowErrors = false;
            $this->rowErrors = [];
        }

        return [
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    public static function sampleText(): string
    {
        return <<<'TXT'
# Separate questions with a blank line.
# Mark the correct choice with * or an Answer line.

What should happen before patient contact?
A. Perform hand hygiene *
B. Remove all PPE
C. Skip preparation
Points: 2

Gloves replace hand hygiene.
True
False *
Points: 1

Type: enumeration
Enumerate the five steps of hand washing.
Points: 5

Type: file_upload
Upload your completed caregiving activity sheet as a PDF or image.
Points: 5
TXT;
    }

    /**
     * @return list<array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}>
     */
    private function fromText(string $contents): array
    {
        $blocks = preg_split("/\n\s*\n/", $this->normalizeNewlines($contents)) ?: [];
        $questions = [];
        $number = 0;

        foreach ($blocks as $block) {
            $lines = [];
            foreach (explode("\n", $block) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $lines[] = $line;
            }

            if ($lines === []) {
                continue;
            }

            $number++;
            $question = $this->capture(fn (): array => $this->questionFromLines($lines, $number));
            if ($question !== null) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * @param  list<string>  $lines
     * @return array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}
     */
    private function questionFromLines(array $lines, int $number): array
    {
        $type = null;
        $points = 1.0;
        $answer = null;
        $promptParts = [];
        $options = [];

        foreach ($lines as $line) {
            if (preg_match('/^(type|points|point|answer|correct|q|question)\s*:\s*(.*)$/i', $line, $matches)) {
                $key = strtolower($matches[1]);
                $value = trim($matches[2]);

                if ($key === 'type') {
                    $type = $this->normalizeType($value, $number);
                } elseif ($key === 'points' || $key === 'point') {
                    $points = $this->parsePoints($value, $number);
                } elseif ($key === 'answer' || $key === 'correct') {
                    $answer = $value;
                } else {
                    $promptParts[] = $value;
                }

                continue;
            }

            if (preg_match('/^([A-F])[\.\)\:]\s+(.+)$/i', $line, $matches)) {
                [$text, $marked] = $this->splitCorrectMarker($matches[2]);
                $options[] = ['text' => $this->cleanOption($text, $number), 'marked' => $marked];

                continue;
            }

            if (preg_match('/^(true|false)\s*(\*)?$/i', $line, $matches)) {
                $options[] = [
                    'text' => ucfirst(strtolower($matches[1])),
                    'marked' => ($matches[2] ?? '') === '*',
                ];

                continue;
            }

            [$text, $marked] = $this->splitCorrectMarker($line);
            if ($marked) {
                $this->fail("Question {$number} marks a correct answer on a line that is not a choice.");
            }

            $promptParts[] = preg_replace('/^\d+[\.\)]\s+/', '', $text) ?? $text;
        }

        return $this->assemble(
            $number,
            $type,
            trim(implode(' ', array_filter($promptParts, fn ($part) => trim($part) !== ''))),
            $options,
            $answer,
            $points,
        );
    }

    /**
     * @return list<array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}>
     */
    private function fromCsv(string $contents): array
    {
        $delimiter = substr_count($contents, ';') > substr_count($contents, ',') ? ';' : ',';
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            $this->fail('The CSV file could not be read.');
        }

        fwrite($handle, $this->normalizeNewlines($contents));
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (! is_array($header)) {
            fclose($handle);
            $this->fail('The CSV file needs a header row with prompt, answer, and option columns.');
        }

        $columns = [];
        foreach ($header as $index => $label) {
            $columns[$index] = $this->csvColumn(trim((string) $label));
        }

        if (! in_array('prompt', $columns, true)) {
            fclose($handle);
            $this->fail('The CSV file needs a prompt column.');
        }

        $questions = [];
        $rowNumber = 1;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            $item = [
                'type' => null,
                'prompt' => '',
                'points' => null,
                'answer' => null,
                'options' => [],
            ];
            $lettered = [];

            foreach ($row as $index => $cell) {
                $column = $columns[$index] ?? null;
                $value = trim((string) $cell);
                if ($column === null || $value === '') {
                    continue;
                }

                if ($column === 'options') {
                    $item['options'] = array_merge($item['options'], array_map('trim', explode('|', $value)));
                } elseif (str_starts_with($column, 'option_')) {
                    $lettered[substr($column, 7)] = $value;
                } else {
                    $item[$column] = $value;
                }
            }

            ksort($lettered);
            $item['options'] = array_merge(array_values($lettered), $item['options']);

            if (trim((string) $item['prompt']) === '' && $item['options'] === []) {
                continue;
            }

            $question = $this->capture(fn (): array => $this->fromStructured($item, count($questions) + count($this->rowErrors) + 1, $rowNumber));
            if ($question !== null) {
                $questions[] = $question;
            }
        }

        fclose($handle);

        return $questions;
    }

    /**
     * @return list<array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}>
     */
    private function fromJson(string $contents): array
    {
        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $this->fail('This file is not valid JSON.');
        }

        if (is_array($data) && isset($data['questions']) && is_array($data['questions'])) {
            $data = $data['questions'];
        }

        if (! is_array($data) || ! array_is_list($data)) {
            $this->fail('JSON must be a list of questions.');
        }

        $questions = [];
        foreach ($data as $item) {
            if (! is_array($item)) {
                $this->fail('Each JSON question must be an object.');
            }

            $question = $this->capture(fn (): array => $this->fromStructured($item, count($questions) + count($this->rowErrors) + 1));
            if ($question !== null) {
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}
     */
    private function fromStructured(array $item, int $number, ?int $rowNumber = null): array
    {
        $where = $rowNumber ? "row {$rowNumber}" : "question {$number}";
        $type = filled($item['type'] ?? null) ? $this->normalizeType((string) $item['type'], $number, $where) : null;
        $prompt = trim((string) ($item['prompt'] ?? $item['question'] ?? ''));
        $points = array_key_exists('points', $item) && $item['points'] !== null && $item['points'] !== ''
            ? $this->parsePoints($item['points'], $number, $where)
            : 1.0;
        $rawOptions = $item['options'] ?? [];

        if (is_string($rawOptions)) {
            $rawOptions = array_map('trim', explode('|', $rawOptions));
        }

        if (! is_array($rawOptions)) {
            $this->fail("Question {$number} options must be a list.");
        }

        $options = [];
        foreach (array_values($rawOptions) as $option) {
            if (is_array($option)) {
                $text = trim((string) ($option['text'] ?? $option['label'] ?? ''));
                $marked = (bool) ($option['correct'] ?? false);
            } else {
                [$text, $marked] = $this->splitCorrectMarker(trim((string) $option));
            }

            if ($text === '') {
                continue;
            }

            $options[] = ['text' => $this->cleanOption($text, $number), 'marked' => $marked];
        }

        $answer = $item['answer'] ?? $item['correct_option'] ?? $item['correct'] ?? null;
        if ($answer === '') {
            $answer = null;
        }

        return $this->assemble($number, $type, $prompt, $options, $answer, $points, $where);
    }

    /**
     * @param  list<array{text: string, marked: bool}>  $options
     * @return array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}
     */
    private function assemble(
        int $number,
        ?string $type,
        string $prompt,
        array $options,
        mixed $answer,
        float $points,
        ?string $where = null,
    ): array {
        $where ??= "question {$number}";
        $labels = array_map(fn (array $option) => $option['text'], $options);
        $marked = array_keys(array_filter($options, fn (array $option) => $option['marked']));

        if (count($marked) > 1) {
            $this->fail(ucfirst($where).' marks more than one correct answer.');
        }

        $markedIndex = $marked[0] ?? null;

        if ($type === null && $this->isTrueFalse($labels)) {
            $type = QuizQuestion::TYPE_TRUE_FALSE;
        }

        if ($type === null && $labels === []) {
            $this->fail(ucfirst($where).' needs answer choices, or a Type line such as enumeration or file_upload.');
        }

        $type ??= QuizQuestion::TYPE_MULTIPLE_CHOICE;

        if (in_array($type, [QuizQuestion::TYPE_FILE_UPLOAD, QuizQuestion::TYPE_ENUMERATION], true)) {
            return [
                'type' => $type,
                'prompt' => $this->cleanPrompt($prompt, $number, $where),
                'options' => [],
                'correct_option' => null,
                'points' => $points,
            ];
        }

        if ($type === QuizQuestion::TYPE_TRUE_FALSE) {
            if ($labels !== [] && ! $this->isTrueFalse($labels)) {
                $this->fail(ucfirst($where).' must use True and False as its choices.');
            }

            $correctIndex = $labels === []
                ? $this->answerIndex(['True', 'False'], $answer, $number, null, $where)
                : $this->answerIndex($labels, $answer, $number, $markedIndex, $where);
            $correctText = ($labels === [] ? ['True', 'False'] : $labels)[$correctIndex];

            return [
                'type' => QuizQuestion::TYPE_TRUE_FALSE,
                'prompt' => $this->cleanPrompt($prompt, $number, $where),
                'options' => ['True', 'False'],
                'correct_option' => strcasecmp($correctText, 'true') === 0 ? 0 : 1,
                'points' => $points,
            ];
        }

        if (count($labels) < 2) {
            $this->fail(ucfirst($where).' needs at least two answer choices.');
        }

        if (count($labels) > 6) {
            $this->fail(ucfirst($where).' can have at most six answer choices.');
        }

        $folded = array_map(fn (string $label) => mb_strtolower($label), $labels);
        if (count($folded) !== count(array_unique($folded))) {
            $this->fail(ucfirst($where).' has duplicate answer choices.');
        }

        return [
            'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'prompt' => $this->cleanPrompt($prompt, $number, $where),
            'options' => $labels,
            'correct_option' => $this->answerIndex($labels, $answer, $number, $markedIndex, $where),
            'points' => $points,
        ];
    }

    /**
     * @param  list<string>  $options
     */
    private function answerIndex(array $options, mixed $answer, int $number, ?int $markedIndex, ?string $where = null): int
    {
        $where ??= "question {$number}";

        if ($markedIndex !== null) {
            if (! array_key_exists($markedIndex, $options)) {
                $this->fail(ucfirst($where).' has a correct answer that is not one of the choices.');
            }

            return $markedIndex;
        }

        if ($answer === null || $answer === '') {
            $this->fail(ucfirst($where).' needs a correct answer. Add * after the correct choice or a line like Answer: B.');
        }

        if (is_int($answer) || (is_string($answer) && preg_match('/^\d+$/', $answer))) {
            $index = (int) $answer;
            if (! array_key_exists($index, $options)) {
                $this->fail(ucfirst($where).' has a correct answer that is not one of the choices.');
            }

            return $index;
        }

        $text = trim((string) $answer);

        if (preg_match('/^([A-F])[\.\)\:]\s*(.*)$/i', $text, $matches)) {
            $index = ord(strtoupper($matches[1])) - 65;
            $rest = trim($matches[2]);
            if (array_key_exists($index, $options) && ($rest === '' || mb_strtolower($options[$index]) === mb_strtolower($rest))) {
                return $index;
            }
        }

        if (preg_match('/^(?:option\s+)?([A-F])$/i', $text, $matches)) {
            $index = ord(strtoupper($matches[1])) - 65;
            if (! array_key_exists($index, $options)) {
                $this->fail(ucfirst($where).' has a correct answer that is not one of the choices.');
            }

            return $index;
        }

        foreach ($options as $index => $option) {
            if (mb_strtolower($option) === mb_strtolower($text)) {
                return $index;
            }
        }

        $this->fail(ucfirst($where).' has a correct answer that does not match one of the choices.');
    }

    /**
     * @param  list<string>  $labels
     */
    private function isTrueFalse(array $labels): bool
    {
        $normalized = array_map(fn (string $label) => ucfirst(strtolower($label)), $labels);

        return count($normalized) === 2
            && array_diff($normalized, ['True', 'False']) === []
            && array_diff(['True', 'False'], $normalized) === [];
    }

    private function normalizeType(string $value, int $number, ?string $where = null): string
    {
        $where ??= "question {$number}";
        $type = strtolower(trim($value));
        $type = str_replace([' ', '-', '/'], '_', $type);

        return match ($type) {
            'multiple_choice', 'multiplechoice', 'mcq', 'choice' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
            'true_false', 'truefalse', 'tf', 'boolean' => QuizQuestion::TYPE_TRUE_FALSE,
            'file_upload', 'file', 'upload', 'activity', 'document' => QuizQuestion::TYPE_FILE_UPLOAD,
            'enumeration', 'enumerate', 'written', 'essay', 'short_answer' => QuizQuestion::TYPE_ENUMERATION,
            default => $this->fail(ucfirst($where).' has an unknown question type.'),
        };
    }

    private function parsePoints(mixed $value, int $number, ?string $where = null): float
    {
        $where ??= "question {$number}";
        $raw = trim((string) $value);
        $raw = preg_replace('/\s*points?$/i', '', $raw) ?? $raw;

        if (! is_numeric($raw)) {
            $this->fail(ucfirst($where).' has an invalid point value.');
        }

        $points = round((float) $raw, 2);
        if ($points < 0.25 || $points > 100) {
            $this->fail(ucfirst($where).' points must be between 0.25 and 100.');
        }

        return $points;
    }

    private function cleanPrompt(string $prompt, int $number, ?string $where = null): string
    {
        $where ??= "question {$number}";
        $prompt = trim(preg_replace('/\s+/u', ' ', strip_tags($prompt)) ?? '');

        if ($prompt === '') {
            $this->fail(ucfirst($where).' needs a question.');
        }

        if (mb_strlen($prompt) > 2000) {
            $this->fail(ucfirst($where).' is longer than 2,000 characters.');
        }

        return $prompt;
    }

    private function cleanOption(string $option, int $number): string
    {
        $option = trim(preg_replace('/\s+/u', ' ', strip_tags($option)) ?? '');

        if ($option === '') {
            $this->fail("Question {$number} has an empty answer choice.");
        }

        if (mb_strlen($option) > 500) {
            $this->fail("Question {$number} has an answer choice longer than 500 characters.");
        }

        return $option;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function splitCorrectMarker(string $value): array
    {
        if (preg_match('/^(.*\S)\s*\*$/u', $value, $matches)) {
            return [trim($matches[1]), true];
        }

        return [trim($value), false];
    }

    private function csvColumn(string $label): ?string
    {
        $label = strtolower(ltrim($label, "\xEF\xBB\xBF"));
        $label = str_replace([' ', '-'], '_', $label);

        return match ($label) {
            'type', 'question_type' => 'type',
            'prompt', 'question', 'text' => 'prompt',
            'points', 'point', 'score' => 'points',
            'answer', 'correct', 'correct_answer', 'correct_option' => 'answer',
            'options', 'choices' => 'options',
            'option_a', 'a', 'choice_a' => 'option_a',
            'option_b', 'b', 'choice_b' => 'option_b',
            'option_c', 'c', 'choice_c' => 'option_c',
            'option_d', 'd', 'choice_d' => 'option_d',
            'option_e', 'e', 'choice_e' => 'option_e',
            'option_f', 'f', 'choice_f' => 'option_f',
            default => null,
        };
    }

    private function normalizeNewlines(string $contents): string
    {
        return str_replace(["\r\n", "\r"], "\n", $contents);
    }

    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        } elseif (str_starts_with($contents, "\xFF\xFE") || str_starts_with($contents, "\xFE\xFF")) {
            $converted = mb_convert_encoding($contents, 'UTF-8', 'UTF-16');
            if (! is_string($converted) || $converted === '') {
                $this->fail('The file must use UTF-8 text.');
            }
            $contents = $converted;
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $this->fail('The file must use UTF-8 text.');
        }

        return $contents;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages([
            $this->errorKey => $message,
        ]);
    }

    private function rowProblem(string $message): void
    {
        if ($this->collectRowErrors) {
            $this->rowErrors[] = $message;

            return;
        }

        $this->fail($message);
    }

    /**
     * @param  callable(): array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}  $build
     * @return array{type: string, prompt: string, options: list<string>, correct_option: int|null, points: float}|null
     */
    private function capture(callable $build): ?array
    {
        try {
            return $build();
        } catch (ValidationException $exception) {
            if (! $this->collectRowErrors) {
                throw $exception;
            }

            $message = collect($exception->errors())->flatten()->first();
            $this->rowErrors[] = is_string($message) ? $message : 'A question could not be read.';

            return null;
        }
    }
}
