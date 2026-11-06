<?php

namespace Tests\Unit;

use App\Services\QuizQuestionImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizQuestionImporterTest extends TestCase
{
    public function test_text_csv_and_json_files_become_quiz_questions(): void
    {
        $importer = new QuizQuestionImporter;

        $fromText = $importer->import(UploadedFile::fake()->createWithContent(
            'questions.txt',
            QuizQuestionImporter::sampleText(),
        ));

        $this->assertCount(4, $fromText);
        $this->assertSame('multiple_choice', $fromText[0]['type']);
        $this->assertSame('What should happen before patient contact?', $fromText[0]['prompt']);
        $this->assertSame(0, $fromText[0]['correct_option']);
        $this->assertSame(2.0, $fromText[0]['points']);
        $this->assertSame('true_false', $fromText[1]['type']);
        $this->assertSame(1, $fromText[1]['correct_option']);
        $this->assertSame(['True', 'False'], $fromText[1]['options']);
        $this->assertSame('enumeration', $fromText[2]['type']);
        $this->assertNull($fromText[2]['correct_option']);
        $this->assertSame('file_upload', $fromText[3]['type']);
        $this->assertSame([], $fromText[3]['options']);

        $csv = <<<'CSV'
type,prompt,points,answer,option_a,option_b,option_c
multiple_choice,Which action comes first?,2,B,Leave the room,Check responsiveness,Call a friend
true_false,Gloves replace hand hygiene.,1,False,True,False,
enumeration,Name two infection risks.,3,,,
CSV;

        $fromCsv = $importer->import(UploadedFile::fake()->createWithContent('questions.csv', $csv));

        $this->assertSame(1, $fromCsv[0]['correct_option']);
        $this->assertSame('Check responsiveness', $fromCsv[0]['options'][1]);
        $this->assertSame('true_false', $fromCsv[1]['type']);
        $this->assertSame(1, $fromCsv[1]['correct_option']);
        $this->assertSame('enumeration', $fromCsv[2]['type']);

        $json = json_encode([[
            'type' => 'multiple_choice',
            'prompt' => 'Which comes first?',
            'points' => 1,
            'options' => ['Hand hygiene', 'Leave the room'],
            'correct_option' => 0,
        ]], JSON_THROW_ON_ERROR);

        $fromJson = $importer->import(UploadedFile::fake()->createWithContent('questions.json', $json));

        $this->assertSame('Which comes first?', $fromJson[0]['prompt']);
        $this->assertSame(0, $fromJson[0]['correct_option']);
        $this->assertSame(0, $fromJson[0]['position']);
    }

    public function test_a_question_without_a_correct_answer_is_rejected(): void
    {
        $importer = new QuizQuestionImporter;

        try {
            $importer->import(UploadedFile::fake()->createWithContent(
                'questions.txt',
                "What comes first?\nA. Hand hygiene\nB. Leave the room\n",
            ));
            $this->fail('A question without an answer was imported.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('correct answer', $exception->validator->errors()->first('file'));
        }
    }

    public function test_preview_lists_a_valid_question_and_blocks_a_bad_row(): void
    {
        $importer = new QuizQuestionImporter;
        $preview = $importer->preview(UploadedFile::fake()->createWithContent(
            'questions.txt',
            "What comes first?\nA. Hand hygiene *\nB. Leave the room\n\nWhat is missing?\nA. One\nB. Two\n",
        ));

        $this->assertCount(1, $preview['questions']);
        $this->assertSame('What comes first?', $preview['questions'][0]['prompt']);
        $this->assertNotEmpty($preview['errors']);
        $this->assertStringContainsString('correct answer', $preview['errors'][0]);
    }
}
