<?php

namespace Tests\Feature;

use App\Models\TrainingProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_programs_section_lists_active_catalog_programs(): void
    {
        TrainingProgram::query()->where('code', 'CAREGIVING-NC-II')->update([
            'description' => 'Official Caregiving NC II catalog description.',
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
        ]);

        $extra = TrainingProgram::query()->create([
            'name' => 'Caregiving NC III',
            'code' => 'CAREGIVING-NC-III',
            'description' => 'Advanced caregiving catalog offering.',
            'total_program_fee' => 30000.00,
            'downpayment_amount' => 3500.00,
            'is_active' => true,
        ]);

        TrainingProgram::query()->create([
            'name' => 'Hidden Draft Program',
            'code' => 'HIDDEN-DRAFT',
            'description' => 'Should not appear on the public landing page.',
            'total_program_fee' => 10000.00,
            'downpayment_amount' => 1000.00,
            'is_active' => false,
        ]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Official training programs from the MCARE catalog.')
            ->assertSee('rel="icon"', false)
            ->assertSee('favicon.png', false)
            ->assertSee('landing-program-grid', false)
            ->assertSee('Caregiving NC II')
            ->assertSee('Official Caregiving NC II catalog description.')
            ->assertSee('CAREGIVING-NC-II')
            ->assertSee('22,000.00')
            ->assertSee('Caregiving NC III')
            ->assertSee('Advanced caregiving catalog offering.')
            ->assertSee(route('applications.create', ['training_program_id' => $extra->id]), false)
            ->assertSee('Admissions path')
            ->assertSee(route('applications.status'), false)
            ->assertSee('Check status')
            ->assertSee('data-landing-chat', false)
            ->assertDontSee('Basic Life Support')
            ->assertDontSee('Hidden Draft Program');

        $this->assertSame(3, substr_count(
            $this->get(route('landing'))->getContent(),
            'landing-program-card'
        ));
    }

    public function test_landing_programs_section_keeps_three_cards_when_three_catalog_programs_exist(): void
    {
        TrainingProgram::query()->where('code', 'CAREGIVING-NC-II')->update([
            'description' => 'Official Caregiving NC II catalog description.',
        ]);

        TrainingProgram::query()->create([
            'name' => 'Caregiving NC III',
            'code' => 'CAREGIVING-NC-III',
            'description' => 'Advanced caregiving catalog offering.',
            'total_program_fee' => 30000.00,
            'downpayment_amount' => 3500.00,
            'is_active' => true,
        ]);

        TrainingProgram::query()->create([
            'name' => 'Basic Caregiving Skills',
            'code' => 'BASIC-CAREGIVING',
            'description' => 'Foundational caregiving skills workshop.',
            'total_program_fee' => 12000.00,
            'downpayment_amount' => 1500.00,
            'is_active' => true,
        ]);

        $html = $this->get(route('landing'))->assertOk()->getContent();

        $this->assertSame(3, substr_count($html, 'landing-program-card'));
        $this->assertStringContainsString('Caregiving NC II', $html);
        $this->assertStringContainsString('Caregiving NC III', $html);
        $this->assertStringContainsString('Basic Caregiving Skills', $html);
        $this->assertStringNotContainsString('Admissions path', $html);
        $this->assertStringNotContainsString('See career support', $html);
        $this->assertStringNotContainsString('https://www.facebook.com/facebook/videos/10153231379946729/', $html);
        $this->assertStringContainsString('No public updates yet', $html);
    }
}
