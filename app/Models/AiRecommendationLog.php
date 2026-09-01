<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRecommendationLog extends Model
{
    protected $fillable = [
        'user_id',
        'prompt',
        'recommendations',
        'provider',
    ];

    protected function casts(): array
    {
        return [
            'recommendations' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
