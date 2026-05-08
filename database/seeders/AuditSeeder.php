<?php

namespace Database\Seeders;

use App\Enums\AuditQuestionInputType;
use App\Models\AuditAnswer;
use App\Models\AuditSection;
use App\Models\AuditType;
use App\Models\AuditQuestion;
use App\Models\AuditSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditSeeder extends Seeder
{
    public function run(): void
    {
        // Create Safety Inspection Type
        $safetyType = AuditType::create([
            'name' => 'Safety Inspection',
            'is_active' => true,
        ]);

        // Create sections for Safety Inspection
        $generalSection = AuditSection::create([
            'audit_type_id' => $safetyType->id,
            'name' => 'General Safety',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $equipmentSection = AuditSection::create([
            'audit_type_id' => $safetyType->id,
            'name' => 'Equipment Check',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        // Create questions for General Safety
        AuditQuestion::create([
            'audit_section_id' => $generalSection->id,
            'question_text' => 'Are all emergency exits clearly marked and unobstructed?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $generalSection->id,
            'question_text' => 'Is the fire extinguisher inspection up to date?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $generalSection->id,
            'question_text' => 'Are all safety signs in good condition and visible?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create questions for Equipment Check
        AuditQuestion::create([
            'audit_section_id' => $equipmentSection->id,
            'question_text' => 'Are all machines properly guarded?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $equipmentSection->id,
            'question_text' => 'Is PPE available and in good condition?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $equipmentSection->id,
            'question_text' => 'Number of safety violations found',
            'is_mandatory' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $equipmentSection->id,
            'question_text' => 'Additional comments about equipment condition',
            'is_mandatory' => false,
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Create Chemical Room Inspection Type
        $chemicalType = AuditType::create([
            'name' => 'Chemical Room Inspection',
            'is_active' => true,
        ]);

        $storageSection = AuditSection::create([
            'audit_type_id' => $chemicalType->id,
            'name' => 'Chemical Storage',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $storageSection->id,
            'question_text' => 'Are all chemicals stored in designated areas?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $storageSection->id,
            'question_text' => 'Are chemical containers properly labeled?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        AuditQuestion::create([
            'audit_section_id' => $storageSection->id,
            'question_text' => 'Is the spill kit fully stocked and accessible?',
            'is_mandatory' => true,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $weeklyaudit = AuditSession::create([
            'audit_type_id' => $safetyType->id,
            'date' => Carbon::now(),
            'user_id' => 1,
            'status' => "submitted",
            'remarks' => "testing",
        ]);

        $questions1 = AuditQuestion::whereHas('section', function ($q) {
            $q->where('audit_type_id', 1);
        })->get();

        $questions2 = AuditQuestion::whereHas('section', function ($q) {
            $q->where('audit_type_id', 2);
        })->get();

        foreach ($questions1 as $question) {
            AuditAnswer::create([
                'audit_session_id' => $weeklyaudit->id,
                'audit_question_id' => $question->id,
                'answer' => 0,
                'remarks' => "testing",
                'checked_at' => Carbon::now(),
            ]);
        }

        foreach ($questions2 as $question) {
            AuditAnswer::create([
                'audit_session_id' => $weeklyaudit->id,
                'audit_question_id' => $question->id,
                'answer' => 0,
                'remarks' => "testing",
                'checked_at' => Carbon::now(),
            ]);
        }
    }
}
