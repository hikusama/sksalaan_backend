<?php

namespace App\Http\Controllers;

use App\Models\YouthInfo;
use Illuminate\Http\Request;

class ValidationFormController extends Controller
{
    public function valStep1(Request $request)
    {

        $request->validate([
            'firstname' => 'required|max:60',
            'middlename' => 'required|max:60',
            'lastname' => [
                'required',
                'max:60',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = YouthInfo::whereRaw(
                        'LOWER(fname) = ? AND LOWER(mname) = ? AND LOWER(lname) = ?',
                        [strtolower($request->firstname), strtolower($request->middlename), strtolower($request->lastname)]
                    )->exists();

                    if ($exists) {
                        $fail('A youth with the same full name already exists.');
                    }
                },
            ],
            'sex' => 'required|in:Male,Female',
            'gender' => 'nullable|max:40',
            'dateOfBirth' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),
            ],
            'address' => 'required|max:100',
        ]);
        return true;
    }
    public function valStep2(Request $request)
    {


        $request->validate([
            'youthType' => 'required|max:100',
            'skills' => 'required|array',
            'skills.*' => 'string|max:100',
            'placeOfBirth' => 'required|max:100',
            'noOfChildren' => 'nullable|integer|min:0|max:30',
            'contactNo' => 'required|regex:/^09\d{9}$/',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:200',
            'civilStatus' => 'required|max:50',
            'occupation' => 'nullable|max:100',
            'religion' => 'required|max:100',
        ]);

        return true;
    }
    public function valStep3(Request $request)
    {
        try {
            if (collect($request->educBg)->filter(function ($item) {
                return !empty($item['level']) ||
                    !empty($item['nameOfSchool']) ||
                    !empty($item['pod']);
            })->isNotEmpty()) {
                $request->validate([
                    'educBg' => 'array|min:1',
                    'educBg.*.level' => 'required|string|max:255',
                    'educBg.*.nameOfSchool' => 'required|string|max:255',
                    'educBg.*.pod' => 'required|string|max:255',
                    'educBg.*.yearGraduate' => 'nullable|integer|between:1995,2100',
                ]);
            }
            return true;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function valStep4(Request $request)
    {
        try {
            //code...

            if (collect($request->civic)->filter(function ($item) {
                return !empty($item['organization']) ||
                    !empty($item['address']) ||
                    !empty($item['start']) ||
                    !empty($item['end']) ||
                    !empty($item['yearGraduated']);
            })->isNotEmpty()) {
                $request->validate([
                    'civic' => 'array|min:1',
                    'civic.*.organization' => 'required|string|max:255',
                    'civic.*.address' => 'required|string|max:255',
                    'civic.*.start' => 'required|date_format:Y-m-d',
                    'civic.*.end' => 'required|string|max:255',
                    'civic.*.yearGraduated' => 'required|integer|between:1995,2100',
                ]);
            }

            return true;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function valStep3b(Request $request)
    {
        if (
            $request->filled('level') ||
            $request->filled('nameOfSchool') ||
            $request->filled('pod')
        ) {
            $request->validate([
                'level' => 'required|string|max:255',
                'nameOfSchool' => 'required|string|max:255',
                'pod' => 'required|string|max:255',
                'yearGraduate' => 'nullable|integer|between:1995,2100',
            ]);
            return true;
        }

        $request->validate([
            'level' => 'nullable',
            'nameOfSchool' => 'nullable',
            'pod' => 'nullable',
            'yearGraduate' => 'nullable',
        ]);

        return true;
    }
    public function valStep4b(Request $request)
    {
        if (
            $request->filled('organization') ||
            $request->filled('orgaddress') ||
            $request->filled('start') ||
            $request->filled('end') ||
            $request->filled('yearGraduated')
        ) {
            $request->validate([
                'organization' => 'required|string|max:255',
                'orgaddress' => 'required|string|max:255',
                'start' => 'required|date_format:Y-m-d',
                'end' => 'required|string|max:255',
                'yearGraduated' => 'required|integer|between:1995,2100',
            ]);
            return true;
        }
        $request->validate([
            'organization' => 'nullable',
            'orgaddress' => 'nullable',
            'start' => 'nullable',
            'end' => 'nullable',
            'yearGraduated' => 'nullable',
        ]);
        return true;
    }


    public function valAge(Request $request)
    {
        $type = $request->get('ageType');
        $allowed = ['range', 'single'];

        if (!in_array($type, $allowed)) {
            return response()->json([
                "type" => $type,
                'error' => [
                    'fail' => "Something went wrong."
                ]
            ], 400);
        }

        if ($type === "range") {
            $request->validate([
                'min' => 'required|integer|min:15|max:29',
                'max' => 'required|integer|min:' . $request->get('min') + 1 . '|max:30',
            ]);
        } else {
            $request->validate([
                'min' => 'required|integer|min:15|max:28',
            ]);
        }

        return response()->json([
            'message' => "success"
        ]);
    }
}
