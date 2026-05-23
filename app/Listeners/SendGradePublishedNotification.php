<?php

namespace App\Listeners;

use App\Events\GradePublished;
use App\Notifications\GradePublishedNotification;

class SendGradePublishedNotification
{
    public function handle(GradePublished $event): void
    {
        $submission = $event->submission->load('student', 'assignment.topic');
        $submission->student->notify(new GradePublishedNotification($submission));
    }
}
