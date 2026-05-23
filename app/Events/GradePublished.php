<?php

namespace App\Events;

use App\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradePublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Submission $submission)
    {
    }
}

    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
