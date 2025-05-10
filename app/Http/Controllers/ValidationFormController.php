<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ValidationFormController extends Controller
{
    public function valStep1(Request $request)
    {
        $request['age'] = (int)$request->input('age');

        $request->validate([
            'firstname' => 'required|max:60',
            'middlename' => 'required|max:60',
            'lastname' => 'required|max:60',
            'sex' => 'required|in:M,F',
            'gender' => 'nullable|max:40',
            'age' => 'required|integer|between:15,30',
            'address' => 'required|max:100',
        ]);
        return true;
    }
    public function valStep2(Request $request)
    {
        $request['height'] = (int)$request->input('height');
        $request['weight'] = (int)$request->input('weight');
        
        $request->validate([
            'youthType' => 'required|max:100',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'dateOfBirth' => 'required|date_format:Y-m-d',
            'placeOfBirth' => 'required|max:100',
            'noOfChildren' => 'nullable|max:100',
            'contactNo' => 'required|max:11|min:11',
            'height' => 'required|integer|max:200',
            'weight' => 'required|integer|max:100',
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
                    !empty($item['pod']) ||
                    !empty($item['yearGraduate']);
                })->isNotEmpty()) {
                $request->validate([
                    'educBg' => 'array|min:1',
                    'educBg.*.level' => 'required|string|max:255',
                    'educBg.*.nameOfSchool' => 'required|string|max:255',
                    'educBg.*.pod' => 'required|string|max:255',
                    'educBg.*.yearGraduate' => 'required|integer|between:1995,2100',
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
            $request->filled('pod') ||
            $request->filled('yearGraduate')
        ) {
            $request->validate([
                'level' => 'required|string|max:255',
                'nameOfSchool' => 'required|string|max:255',
                'pod' => 'required|string|max:255',
                'yearGraduate' => 'required|integer|between:1995,2100',
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
}
