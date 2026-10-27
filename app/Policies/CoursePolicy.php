<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Admins may manage every course; teachers only the courses they teach.
     */
    public function manage(User $user, Course $course): bool
    {
        return $user->isAdmin() || ($user->isTeacher() && $course->instructor_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->canManageCourses();
    }

    /**
     * Enrolled students, the instructor and admins may open the learning pages.
     */
    public function learn(User $user, Course $course): bool
    {
        return $this->manage($user, $course) || $user->isEnrolledIn($course);
    }
}
