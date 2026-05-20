<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'assignment_id', 'student_id', 'file_path',
        'submitted_text', 'grade', 'teacher_comment', 'status', 'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'graded_at' => 'datetime',
            'grade'     => 'decimal:2',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function isLate(): bool
    {
        return $this->created_at->isAfter($this->assignment->due_date);
    }
}
