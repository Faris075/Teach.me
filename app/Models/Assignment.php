<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'classroom_id', 'topic_id', 'title', 'description',
        'file_path', 'due_date', 'allow_late_submissions',
        'grading_type', 'max_score',
    ];

    protected function casts(): array
    {
        return [
            'due_date'               => 'datetime',
            'allow_late_submissions' => 'boolean',
            'max_score'              => 'decimal:2',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function submissionFor(int $studentId): ?Submission
    {
        return $this->submissions()->where('student_id', $studentId)->first();
    }
}
