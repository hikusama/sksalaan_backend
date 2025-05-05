<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ValidationFormController extends Controller
{
    public function valStep1(Request $request) {
        $request->validate([
            'firstname' => 'required',
            'middlename' => 'required',
            'lastname' => 'required',
            'age' => 'required|integer|between:15,30',
            'address' => 'required',
            'sex' => 'required|in:M,F',
            'gender' => 'max:40',
        ]);
        return true;
    }
    public function valStep2(Request $request) {
        $request->validate([
            'youthType' => 'required|max:255',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'dateOfBirth' => 'required|date_format:Y-m-d',
            'placeOfBirth' => 'required|max:100',
            'noOfChildren' => 'integer|max:100',
            'height' => 'required|integer|max:200',
            'weight' => 'required|integer|max:100',
            'civilStatus' => 'required|max:50',
            'occupation' => 'max:100',
            'religion' => 'required|max:100',
        ]);
        return true;
    }
    public function valStep3(Request $request) {
        if (collect($request->educBg)->filter(function ($item) {
            return !empty($item['level']) || 
                   !empty($item['nameOfSchool']) || 
                   !empty($item['periodOfAttendance']) || 
                   !empty($item['yearGraduate']);
        })->isNotEmpty()) {
            $request->validate([
                'educBg' => 'array|min:1',
                'educBg.*.level' => 'required|string|max:255',
                'educBg.*.nameOfSchool' => 'required|string|max:255',
                'educBg.*.periodOfAttendance' => 'required|string|max:255',
                'educBg.*.yearGraduate' => 'required|string|max:4',
            ]);
            return true;
        }

        $request->validate([
            'level' => 'nullable',
            'nameOfSchool' => 'nullable',
            'periodOfAttendance' => 'nullable',
            'yearGraduate' => 'nullable',
        ]);
        return true;

    }
    public function valStep4(Request $request) {
        if (
            $request->filled('nameOfOrganization') ||
            $request->filled('addressOfOrganization') ||
            $request->filled('start') ||
            $request->filled('end') ||
            $request->filled('yearGraduated')
        ) {
            return $request->validate([
                'nameOfOrganization' => 'required',
                'addressOfOrganization' => 'required',
                'start' => 'required',
                'end' => 'required',
                'yearGraduated' => 'required',
            ]);
        }
        return $request->validate([
            'nameOfOrganization' => 'nullable',
            'addressOfOrganization' => 'nullable',
            'start' => 'nullable',
            'end' => 'nullable',
            'yearGraduated' => 'nullable',
        ]);
    }
}