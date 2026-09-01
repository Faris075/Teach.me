<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\User;

class CourseProgressService
{
    public function progressForStudentInClassroom(User $student, Classroom $classroom): float
    {
        $assignmentIds = $classroom->assignments()->pluck('id');
        $total = $assignmentIds->count();

        if ($total === 0) {
            return 0.0;
        }

        $submitted = $student->submissions()
            ->whereIn('assignment_id', $assignmentIds)
            ->count();

        return round(($submitted / $total) * 100, 1);
    }
}
