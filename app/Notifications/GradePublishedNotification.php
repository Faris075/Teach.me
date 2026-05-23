<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GradePublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Submission $submission)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $assignment = $this->submission->assignment;

        return [
            'assignment_id'   => $assignment->id,
            'assignment_title' => $assignment->title,
            'classroom_id'    => $assignment->topic->classroom_id,
            'grade_numeric'   => $this->submission->grade_numeric,
            'grade_literal'   => $this->submission->grade_literal,
            'feedback'        => $this->submission->teacher_comment,
            'url'             => route('student.assignments.show', $assignment),
        ];
    }
}

            //
        ];
    }
}
