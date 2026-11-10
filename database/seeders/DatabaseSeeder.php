<?php

namespace Database\Seeders;

use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with standard MCARE portal accounts.
     */
    public function run(): void
    {
        RolePermissionMatrix::ensureConfigured();

        $accounts = [
            [
                'name' => 'MCARE Administrator',
                'email' => 'admin@mcare.com',
                'role' => 'admin',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'MCARE Trainer',
                'email' => 'trainer@mcare.com',
                'role' => 'trainer',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Approved Trainee',
                'email' => 'trainee@mcare.com',
                'role' => 'trainee',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'New Applicant',
                'email' => 'applicant@mcare.com',
                'role' => 'applicant',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ],
        ];

        $seededUsers = [];

        foreach ($accounts as $accountData) {
            $user = User::updateOrCreate(
                ['email' => $accountData['email']],
                $accountData
            );

            RolePermissionMatrix::syncUser($user);
            $seededUsers[$accountData['role']] = $user;
        }

        $demoBatch = TrainingBatch::updateOrCreate(
            ['name' => 'Demo Batch', 'year' => 2026],
            [
                'trainer_id' => $seededUsers['trainer']->id,
                'is_active' => true,
                'enrollment_ends_at' => now()->addYear(),
                'training_starts_at' => now()->subMonth(),
                'training_ends_at' => now()->addMonth(),
                'am_days' => 'MWF',
                'pm_days' => 'TTS',
            ]
        );

        EnrollmentApplication::updateOrCreate(
            ['user_id' => $seededUsers['trainee']->id],
            [
                'training_batch_id' => $demoBatch->id,
                'email' => $seededUsers['trainee']->email,
                'program' => 'Caregiving NC II',
                'first_name' => 'Approved',
                'last_name' => 'Trainee',
                'birth_date' => '2000-01-01',
                'gender' => 'Female',
                'contact_number' => '09170000000',
                'schedule_preference' => 'AM',
                'street' => '1 Training Street',
                'barangay' => 'Central',
                'city' => 'Iriga City',
                'province' => 'Camarines Sur',
                'zip_code' => '4431',
                'educational_attainment' => 'College Graduate',
                'school_name' => 'MCARE School',
                'year_graduated' => 2022,
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            ]
        );

        $alumni = User::updateOrCreate(
            ['email' => 'alumni@mcare.com'],
            [
                'name' => 'MCARE Alumni Demo',
                // Graduates keep the trainee role and unlock Career Hub through learning_status.
                'role' => 'trainee',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        RolePermissionMatrix::syncUser($alumni);
        EnrollmentApplication::updateOrCreate(
            ['user_id' => $alumni->id],
            [
                'training_batch_id' => $demoBatch->id,
                'email' => $alumni->email,
                'program' => 'Caregiving NC II',
                'first_name' => 'MCARE',
                'last_name' => 'Alumni Demo',
                'birth_date' => '1995-01-01',
                'gender' => 'Female',
                'contact_number' => '09170000001',
                'schedule_preference' => 'AM',
                'street' => '1 Training Street',
                'barangay' => 'Central',
                'city' => 'Iriga City',
                'province' => 'Camarines Sur',
                'zip_code' => '4431',
                'educational_attainment' => 'College Graduate',
                'school_name' => 'MCARE School',
                'year_graduated' => 2022,
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ]
        );
        $alumni->alumniProfile()->updateOrCreate([], [
            'is_available_for_duty' => true,
            'availability_updated_at' => now(),
        ]);

        foreach ([
            [
                'title' => 'Live-in caregiver, Iriga City',
                'estimated_salary' => '₱18,000 / month',
                'estimated_start_date' => now()->addDays(14)->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 72,
                'specific_contraptions' => 'Walker',
                'condition_summary' => 'Needs mobility support during daily routines.',
            ],
            [
                'title' => 'Bedside caregiver, Naga City',
                'estimated_salary' => '₱20,000 / month',
                'estimated_start_date' => now()->addDays(21)->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_MALE,
                'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
                'patient_age' => 80,
                'specific_contraptions' => 'Hospital bed',
                'condition_summary' => 'Requires assistance with repositioning and comfort routines.',
            ],
        ] as $opportunity) {
            CareerOpportunity::updateOrCreate(
                [
                    'title' => $opportunity['title'],
                    'employer' => 'MCARE-Coordinated Placement',
                ],
                [
                    ...$opportunity,
                    'created_by_id' => $seededUsers['admin']->id,
                    'description' => 'Privacy-minimal career posting managed through the MCARE Alumni Hub.',
                    'is_published' => true,
                    'published_at' => now(),
                ]
            );
        }

        $this->call(SampleGraduateSeeder::class);
    }
}
