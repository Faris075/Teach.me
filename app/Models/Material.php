<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    protected $fillable = ['topic_id', 'title', 'content', 'file_path'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
