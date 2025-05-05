<?php

namespace App\Http\Controllers;

use App\Models\civicInvolvement;
use App\Models\EducBG;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YouthUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return YouthUser::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate and create the youth user
            $fields = $request->validate([
                'youthType' => 'required|max:255',
            ]);
            $yUser = YouthUser::create($fields);

            // Validate and create the youth info
            $fields2 = $this->validateYouthInfo($request);
            $fields2['youth_user_id'] = $yUser->id;
            $info = YouthInfo::create($fields2);

            // Validate and create the education background
            $fields3 = $this->validateEducBG($request);
            $fields3['youth_user_id'] = $yUser->id;
            $eucbg = EducBG::create($fields3);

            // Validate and create the civic involvement
            $fields4 = $this->validateCivicInvolvement($request);
            $fields4['youth_user_id'] = $yUser->id;
            $civic = CivicInvolvement::create($fields4);

            DB::commit();
            return response()->json([
                'yUser' => $yUser,
                'info' => $info,
                'eucbg' => $eucbg,
                'civic' => $civic,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(), // this gives the field-specific error messages
            ], 422);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create youth user.',
                'details' => $th->getMessage(),
            ], 500);
        }
    }

    private function validateYouthStep1(Request $request){

    }
    private function validateYouthInfo(Request $request)
    {
        return $request->validate([
            'fname' => 'required',
            'mname' => 'required',
            'lname' => 'required',
            'age' => 'required',
            'address' => 'required',
            'dateOfBirth' => 'required|date_format:Y-m-d',
            'placeOfBirth' => 'required',
            'height' => 'required|integer',
            'weight' => 'required|integer',
            'religion' => 'required',
            'occupation' => 'required',
            'sex' => 'required|in:M,F',
            'civilStatus' => 'required',
            'noOfChildren' => 'max:20',
        ]);
    }

    private function validateEducBG(Request $request)
    {

        if (
            $request->filled('level') ||
            $request->filled('nameOfSchool') ||
            $request->filled('periodOfAttendance') ||
            $request->filled('yearGraduate')
        ) {
            return $request->validate([
                'level' => 'required',
                'nameOfSchool' => 'required',
                'periodOfAttendance' => 'required',
                'yearGraduate' => 'required',
            ]);
        }

        return $request->validate([
            'level' => 'nullable',
            'nameOfSchool' => 'nullable',
            'periodOfAttendance' => 'nullable',
            'yearGraduate' => 'nullable',
        ]);
    }

    private function validateCivicInvolvement(Request $request)
    {
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

    /**
     * Display the specified resource.
     */
    public function show(YouthUser $youth)
    {
        // $yuser->load('info');

        return [
            'yuser' => $youth,
            'info' => $youth->info,
        ];
        //         $yuser = YouthUser::with('info')->find($id);
        // return $yuser->info;


    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, YouthUser $youth)
    {




        $fields = $request->validate(['youthType' => 'required|max:255']);
        $fields2 = $this->validateYouthInfo($request);
        $fields3 = $this->validateEducBG($request);
        $fields4 = $this->validateCivicInvolvement($request);

        $youth->load('info', 'educbg', 'civicInvolvement');


        $changed = false;

        $youth->fill($fields);
        if ($youth->isDirty()) {
            $youth->save();
            $changed = true;
        }

        if ($youth->info) {
            $youth->info->fill($fields2);
            if ($youth->info->isDirty()) {
                $youth->info->save();
                $changed = true;
            }
        }

        if ($youth->educbg) {
            $youth->educbg->fill($fields3);
            if ($youth->educbg->isDirty()) {
                $youth->educbg->save();
                $changed = true;
            }
        }

        if ($youth->civicInvolvement) {
            $youth->civicInvolvement->fill($fields4);
            if ($youth->civicInvolvement->isDirty()) {
                $youth->civicInvolvement->save();
                $changed = true;
            }
        }

        $msg = $changed ? 'Youth User updated successfully' : 'Nothing to update';

        return response()->json(['message' => $msg, 'youth' => $youth]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(YouthUser $youth)
    {
        // $msg = 'Something went wrong ';
        // try {
        //     $youth->delete();
        //     $msg = 'Deleted successfylly';

        // } catch (\Exception $th) {
        //     $msg .= $th->getMessage();
        // }
        $youth->delete();


        return response()->json(['message' => 'Deleted successfylly']);
    }
}
