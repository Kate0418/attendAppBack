<?php

namespace App\Http\Controllers;

use App\Models\Time;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Log;
use Exception;

class CourseController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            "course.name" => "required|string|max:255",
            "course.gradeId" => "required|integer",

            "course.times.*.period" => "required|integer",
            "course.times.*.startTime" => "required|string|max:255",
            "course.times.*.endTime" => "required|string|max:255",

            "course.lessons.*.dayOfWeek" => "required|integer",
            "course.lessons.*.period" => "required|integer",
            "course.lessons.*.subjectId" => "required|integer",
        ]);

        $company_id = $user->company_id;
        $course = $request->course;

        $course_id = Course::create([
            "name" => $course["name"],
            "grade_id" => $course["gradeId"],
            "company_id" => $company_id,
        ])->id;

        try {
            DB::transaction(function () use ($course_id, $course, $company_id) {
                foreach ($course["lessons"] as $lesson) {
                    Lesson::create([
                        "course_id" => $course_id,
                        "subject_id" => $lesson["subjectId"],
                        "day_of_week" => $lesson["dayOfWeek"],
                        "period" => $lesson["period"],
                        "company_id" => $company_id,
                    ]);
                }

                foreach ($course["times"] as $time) {
                    Time::create([
                        "course_id" => $course_id,
                        "period" => $time["period"],
                        "start_time" => $time["startTime"],
                        "end_time" => $time["endTime"],
                    ]);
                }
            });
        } catch (Exception $e) {
            Log::error($e);
            return response()->json(
                [
                    "success" => false,
                    "message" => "コースの登録に失敗しました。",
                ],
                500
            );
        }

        return response()->json(
            [
                "success" => true,
                "message" => "コースを追加しました。",
            ],
            201
        );
    }

    public function select()
    {
        $user = Auth::user();
        $courses = Course::where("company_id", $user->company_id)
            ->get()
            ->map(function ($course) {
                return [
                    "value" => $course->id,
                    "label" => $course->name,
                ];
            });
        return response()->json([
            "success" => true,
            "courses" => $courses,
        ]);
    }
}
