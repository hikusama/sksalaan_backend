<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckAdmin;
use App\Models\civicInvolvement;
use App\Models\EducBG;
use App\Models\User;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YouthUserController extends Controller
{
    // implements HasMiddleware
    // public static function middleware()
    // {
    //     return [
    //         new Middleware('auth:sanctum', except: ['registerYouth']),
    //         new Middleware(CheckAdmin::class, except: ['registerYouth']),
    //     ];
    // }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return YouthUser::all();
    }


    public function searchName(Request $request)
    {

        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'fname');
        $typeId = $request->input('typeId');

        $allowedFilters = ['fname', 'lname', 'age', 'created_at'];
        if (!in_array($sortBy, $allowedFilters)) {
            return response()->json(['error' => 'Invalid filter field'], 400);
        }

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $results = YouthInfo::where(function ($query) use ($search) {
            $query->where('fname', 'LIKE', '%' . $search . '%')
                ->orWhere('mname', 'LIKE', '%' . $search . '%')
                ->orWhere('lname', 'LIKE', '%' . $search . '%');
        })
            ->when(!is_null($typeId), function ($query) use ($typeId) {
                $linked = filter_var($typeId, FILTER_VALIDATE_BOOLEAN);
                return $linked
                    ? $query->whereHas('yUser', function ($q) {
                        $q->whereNotNull('user_id');
                    })
                    : $query->whereHas('yUser', function ($q) {
                        $q->whereNull('user_id');
                    });
            })
            ->orderBy($sortBy, 'ASC')
            ->with([
                'yUser',
                'yUser.educbg',
                'yUser.civicInvolvement'
            ])
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'typeId' => $typeId,
                'sortBy' => $sortBy,
                'perPage' => $perPage,
                'page' => $page
            ]);

        $pass = $results->map(function ($info) {
            return [
                'youthUser' => [
                    $info
                ]
            ];
        });

        return response()->json([
            'data' => $pass,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
            $fields = $request->validate([
                'youthType' => 'required|max:50',
                'skillsf' => 'required|max:100',
            ]);
            $renamedFields = [];
            foreach ($fields as $key => $value) {
                if ($key === 'skillsf') {
                    $renamedFields['skills'] = $value;
                } else {
                    $renamedFields[$key] = $value;
                }
            }

            $renamedFields['user_id'] = $request->user()->id;
            $yUser = YouthUser::create($renamedFields);

            $fields2 = $this->validateYouthInfo($request);
            $fields2['youth_user_id'] = $yUser->id;
            $info = YouthInfo::create($fields2);

            $educbgData = $this->validateEducBG($request);
            $educbg = [];

            if ($educbgData) {
                $now = now();
                foreach ($educbgData['educBg'] as $item) {
                    $educbg[] = [
                        'youth_user_id' => $yUser->id,
                        'level' => $item['level'],
                        'nameOfSchool' => $item['nameOfSchool'],
                        'periodOfAttendance' => $item['pod'],
                        'yearGraduate' => $item['yearGraduate'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                EducBG::insert($educbg);
            }

            $civicData = $this->validateCivicInvolvement($request);
            $civic = [];

            if ($civicData) {
                $now = now();
                foreach ($civicData['civic'] as $item) {
                    $civic[] = [
                        'youth_user_id' => $yUser->id,
                        'nameOfOrganization' => $item['organization'],
                        'addressOfOrganization' => $item['orgaddress'],
                        'start' => $item['start'],
                        'end' => $item['end'],
                        'yearGraduated' => $item['yearGraduated'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                CivicInvolvement::insert($civic);
            }


            DB::commit();
            return response()->json([
                'adssad' => $request->user()->id,
                'ads' => $renamedFields,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
            ], 422);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create youth user.',
                'details' => $th->getMessage(),
            ], 500);
        }
    }


    public function registerYouth(Request $request)
    {

        DB::beginTransaction();
        try {
            $fields = $request->validate([
                'youthType' => 'required|max:50',
                'skillsf' => 'required|max:100',
            ]);
            $renamedFields = [];
            foreach ($fields as $key => $value) {
                if ($key === 'skillsf') {
                    $renamedFields['skills'] = $value;
                } else {
                    $renamedFields[$key] = $value;
                }
            }
            // $renamedFields['user_id'] = null;
            $yUser = YouthUser::create($renamedFields);

            $fields2 = $this->validateYouthInfo($request);
            $fields2['youth_user_id'] = $yUser->id;
            $info = YouthInfo::create($fields2);

            $educbgData = $this->validateEducBG($request);
            $educbg = [];

            if ($educbgData) {
                $now = now();
                foreach ($educbgData['educBg'] as $item) {
                    $educbg[] = [
                        'youth_user_id' => $yUser->id,
                        'level' => $item['level'],
                        'nameOfSchool' => $item['nameOfSchool'],
                        'periodOfAttendance' => $item['pod'],
                        'yearGraduate' => $item['yearGraduate'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                EducBG::insert($educbg);
            }

            $civicData = $this->validateCivicInvolvement($request);
            $civic = [];

            if ($civicData) {
                $now = now();
                foreach ($civicData['civic'] as $item) {
                    $civic[] = [
                        'youth_user_id' => $yUser->id,
                        'nameOfOrganization' => $item['organization'],
                        'addressOfOrganization' => $item['orgaddress'],
                        'start' => $item['start'],
                        'end' => $item['end'],
                        'yearGraduated' => $item['yearGraduated'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                CivicInvolvement::insert($civic);
            }


            DB::commit();
            return response()->json(true);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
            ], 422);
        } catch (\Exception $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create youth user.',
                'details' => $th->getMessage(),
            ], 500);
        }
    }

    private function validateYouthInfo(Request $request)
    {
        $request['height'] = (int)$request->input('height');
        $request['weight'] = (int)$request->input('weight');
        $request['age'] = (int)$request->input('age');

        $fields = $request->validate([
            'firstname' => 'required|max:60',
            'middlename' => 'required|max:60',
            'lastname' => 'required|max:60',
            'sex' => 'required|in:M,F',
            'gender' => 'nullable|max:40',
            'age' => 'required|integer|between:15,30',
            'address' => 'required|max:100',
            'dateOfBirth' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),  
            ],            'placeOfBirth' => 'required|max:100',
            'contactNo' => 'required|max:10|min:10',
            'height' => 'required|integer|max:300',
            'weight' => 'required|integer|max:200',
            'religion' => 'required|max:100',
            'occupation' => 'nullable|max:100',
            'civilStatus' => 'required|max:100',
            'noOfChildren' => 'nullable|max:30',
        ]);
        $renamedFields = [];
        foreach ($fields as $key => $value) {
            if ($key === 'firstname') {
                $renamedFields['fname'] = $value;
            } elseif ($key === 'middlename') {
                $renamedFields['mname'] = $value;
            } elseif ($key === 'lastname') {
                $renamedFields['lname'] = $value;
            } else {
                $renamedFields[$key] = $value;
            }
        }
        return $renamedFields;
    }

    private function validateEducBG(Request $request)
    {

        if (collect($request->educBg)->filter(
            fn($item) =>
            !empty($item['level']) ||
                !empty($item['nameOfSchool']) ||
                !empty($item['pod']) ||
                !empty($item['yearGraduate'])
        )->isNotEmpty()) {
            return $request->validate([
                'educBg' => 'array|min:1',
                'educBg.*.level' => 'required|string|max:255',
                'educBg.*.nameOfSchool' => 'required|string|max:255',
                'educBg.*.pod' => 'required|string|max:255',
                'educBg.*.yearGraduate' => 'required|integer|between:1995,2100',
            ]);
        }
        return false;
    }

    private function validateCivicInvolvement(Request $request)
    {
        if (collect($request->civic)->filter(function ($item) {
            return !empty($item['organization']) ||
                !empty($item['orgaddress']) ||
                !empty($item['start']) ||
                !empty($item['end']) ||
                !empty($item['yearGraduated']);
        })->isNotEmpty()) {
            return $request->validate([
                'civic' => 'array|min:1',
                'civic.*.organization' => 'required|string|max:255',
                'civic.*.orgaddress' => 'required|string|max:255',
                'civic.*.start' => 'required|date_format:Y-m-d',
                'civic.*.end' => 'required|string|max:255',
                'civic.*.yearGraduated' => 'required|integer|between:1995,2100',
            ]);
        }
        return false;
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
    public function youthApprove(Request $request, YouthUser $youth)
    {
        $user = User::findOrFail($request->input('user_id'));
        $yUser = YouthUser::findOrFail($request->input('youthid'));
        $yUser->user_id =  $user->id;
        $yUser->save();
        return 'Success';
    }

    public function update(Request $request, YouthUser $youth)
    {
        $fields = $request->validate([
            'youthType' => 'required|max:255',
            'skillsf' => 'required|max:100',
        ]);

        $fields2 = $this->validateYouthInfo($request);
        $fields3 = $this->validateEducBG($request);
        $fields4 = $this->validateCivicInvolvement($request);

        $youth->load('info', 'educbg', 'civicInvolvement');

        // Fix: map skillsf to skills
        $ryt = [];
        foreach ($fields as $key => $value) {
            $ryt[$key === 'skillsf' ? 'skills' : $key] = $value;
        }

        $changed = false;

        $youth->fill($ryt);
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

        $erows = [];
        if (isset($fields3['educBg'])) {
            foreach ($fields3['educBg'] as $item) {
                $erows[] = [
                    'id' => $item['id'] ?? null,
                    'level' => $item['level'],
                    'nameOfSchool' => $item['nameOfSchool'],
                    'periodOfAttendance' => $item['pod'],
                    'yearGraduate' => $item['yearGraduate'],
                ];
            }
        }

        $educIds = collect($erows)->pluck('id')->filter();
        $youth->educbg()->whereNotIn('id', $educIds)->delete();

        foreach ($erows as $educData) {
            if (!empty($educData['id'])) {
                $educ = $youth->educbg()->find($educData['id']);
                if ($educ) {
                    $educ->fill($educData);
                    if ($educ->isDirty()) {
                        $educ->save();
                        $changed = true;
                    }
                }
            } else {
                $youth->educbg()->create($educData);
                $changed = true;
            }
        }

        $crows = [];
        if (isset($fields4['civic'])) {
            foreach ($fields4['civic'] as $item) {
                $crows[] = [
                    'id' => $item['id'] ?? null,
                    'nameOfOrganization' => $item['organization'],
                    'addressOfOrganization' => $item['orgaddress'],
                    'start' => $item['start'],
                    'end' => $item['end'],
                    'yearGraduated' => $item['yearGraduated'],
                ];
            }
        }

        $civicIds = collect($crows)->pluck('id')->filter();
        $youth->civicInvolvement()->whereNotIn('id', $civicIds)->delete();

        foreach ($crows as $civicData) {
            if (!empty($civicData['id'])) {
                $civic = $youth->civicInvolvement()->find($civicData['id']);
                if ($civic) {
                    $civic->fill($civicData);
                    if ($civic->isDirty()) {
                        $civic->save();
                        $changed = true;
                    }
                }
            } else {
                $youth->civicInvolvement()->create($civicData);
                $changed = true;
            }
        }

        $msg = $changed ? 'Updated successfully...' : 'Nothing to update';

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
