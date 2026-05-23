<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->string('file_path')->nullable();
            $table->dateTime('due_date');
            $table->boolean('allow_late_submissions')->default(true);
            $table->enum('grading_type', ['percentage', 'igcse_letter'])->default('percentage');
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->longText('submitted_text')->nullable();
            $table->decimal('grade_numeric', 5, 2)->nullable();
            $table->string('grade_literal', 4)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->enum('status', ['submitted', 'graded', 'turned_in_late'])->default('submitted');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
    }
};
