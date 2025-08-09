<?php

namespace App\Http\Controllers;

use App\Http\Middleware\CheckAdmin;
use App\Models\Bulk_logger;
use App\Models\civicInvolvement;
use App\Models\EducBG;
use App\Models\RegistrationCycle;
use App\Models\User;
use App\Models\ValidateYouth;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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


    public function searchName(Request $request)
    {
        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'fname');
        $typeId = $request->input('typeId');
        $cycleID = $request->input('cID');

        $youthType = strtolower($request->input('youthType'));
        $sex = strtolower($request->input('sex'));
        $gender = strtolower($request->input('gender'));
        $civilStatus = strtolower($request->input('civilStatus'));
        $ageType = $request->input('ageType');
        $ageValue = $request->input('ageValue', []);
        $qualification = strtolower($request->input('qualification', ''));

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }

        if ($cycleID !== 'all') {
            RegistrationCycle::findOrFail($cycleID);
        }

        $allowedFilters = ['fname', 'lname', 'age', 'created_at'];
        if (!in_array($sortBy, $allowedFilters)) {
            return response()->json(['error' => 'Invalid filter field'], 400);
        }

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        // whereHas('yUser.validated', function ($q) use ($cycleID) {
        //             $q->where('registration_cycle_id', $cycleID);
        //         })
        $query = YouthInfo::when($cycleID !== 'all', function ($qq) use ($cycleID) {
            $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID);
            });
        })
            ->where(function ($query) use ($search) {
                $query->where('fname', 'LIKE', '%' . $search . '%')
                    ->orWhere('mname', 'LIKE', '%' . $search . '%')
                    ->orWhere('lname', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('yUser', function ($q) use ($search) {
                        $q->where('batchNo', 'LIKE', '%' . $search . '%');
                    });
            })
            ->when(!empty($youthType), fn($q) => $q->where('youthType', $youthType))
            ->when(!empty($sex), fn($q) => $q->where('sex', $sex))
            ->when(!empty($gender), fn($q) => $q->where('gender', $gender))
            ->when(!empty($civilStatus), fn($q) => $q->where('civilStatus', $civilStatus))
            ->when(true, function ($q) use ($request, $ageType, $ageValue, $ageExpression) {
                $qualification = strtolower($request->input('qualification', ''));

                if ($qualification === 'qualified') {
                    // Apply only the filters coming from the frontend
                    if ($ageType === 'single' && !empty($ageValue['min'])) {
                        $q->whereRaw("$ageExpression = ?", [intval($ageValue['min'])]);
                    }
                    if ($ageType === 'range' && !empty($ageValue['min']) && !empty($ageValue['max'])) {
                        $q->whereBetween(DB::raw($ageExpression), [
                            intval($ageValue['min']),
                            intval($ageValue['max'])
                        ]);
                    }
                } elseif ($qualification === 'unqualified') {
                    // Unqualified means outside 15–30
                    $q->where(function ($sub) use ($ageExpression) {
                        $sub->where(DB::raw($ageExpression), '<', 15)
                            ->orWhere(DB::raw($ageExpression), '>', 30);
                    });
                }
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
            ->with([
                'yUser',
                'yUser.educbg',
                'yUser.civicInvolvement'
            ]);

        // Handle sorting by age without selecting it
        if ($sortBy === 'age') {
            $query->orderByRaw("$ageExpression DESC");
        } else {
            $query->orderBy($sortBy, 'DESC');
        }

        $results = $query->paginate($perPage)->appends($request->all());

        // Keep the same structure for frontend
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
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }

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
            $batchNo = $this->generateUnique7DigitCode('youth_users', 'batchNo');
            $youth_personal_id = $this->generateUnique7DigitCode('youth_users', 'youth_personal_id');

            $renamedFields['youth_personal_id'] = $youth_personal_id;
            $renamedFields['batchNo'] = $batchNo;
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
            ValidateYouth::create([
                'youth_user_id' => $yUser->id,
                'registration_cycle_id' => $cycleID,
            ]);


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
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
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
            $batchNo = $this->generateUnique7DigitCode('youth_users', 'batchNo');
            $youth_personal_id = $this->generateUnique7DigitCode('youth_users', 'youth_personal_id');

            $renamedFields['youth_personal_id'] = $youth_personal_id;
            $renamedFields['batchNo'] = $batchNo;
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
            'sex' => 'required|in:Male,Female',
            'gender' => 'nullable|max:40',
            'age' => 'required|integer|between:15,30',
            'address' => 'required|max:100',
            'dateOfBirth' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),
            ],
            'placeOfBirth' => 'required|max:100',
            'contactNo' => 'required|max:10|min:10',
            'height' => 'nullable|integer|max:300',
            'weight' => 'nullable|integer|max:200',
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


    public function validateYouth(int $id)
    {
        $cycleID = $this->getCycle();
        if (!$cycleID) {
            ValidateYouth::create([
                'youth_user_id' => $id,
                'registration_cycle_id' => $cycleID,
            ]);
        }
    }


    public function show(YouthUser $youth)
    {

        return [
            'yuser' => $youth,
            'info' => $youth->info,
        ];
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
                'educBg.*.yearGraduate' => 'nullable|integer|between:1995,2100',
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




    public function migrateFromMobile(Request $request)
    {

        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['cycleClose' => 'No active cycle.'], 400);
        }
        $migrateData = $request->all();

        $attempted = count($migrateData);
        $failed = [];
        $submitted = [];
        $readyData = [];

        foreach ($migrateData as $data) {
            try {
                $userValidator = Validator::make($data['user'], [
                    'youthType' => 'required|max:50',
                    'skills' => 'required|max:100',
                ]);
                if ($userValidator->fails()) {
                    throw new \Exception('User validation failed');
                }

                $infoRequest = new Request($data['info']);
                $validatedInfo = $this->validateYouthInfoRaw($infoRequest);

                $educRequest = new Request(['educBg' => $data['educBG']]);
                $validatedEduc = $this->validateEducBGRaw($educRequest);

                $civicRequest = new Request(['civic' => $data['civic']]);
                $validatedCivic = $this->validateCivicInvolvementRaw($civicRequest);

                $readyData[] = [
                    'user' => $data['user'],
                    'info' => $validatedInfo,
                    'educBG' => $validatedEduc ?: [],
                    'civic' => $validatedCivic ?: [],
                ];

                $submitted[] = $data['user']['id'] ?? null;
            } catch (\Throwable $th) {
                Log::info("error validation" . $th->getMessage());

                $failed[] = $data['user']['id'] ?? null;
            }
        }
        $uuid = $request->user()->id;

        // $uuid = 2;
        DB::beginTransaction();
        try {
            $youth_personal_id = $this->generateUnique7DigitCode('youth_users', 'youth_personal_id');

            $batchNo = $this->generateUnique7DigitCode('youth_users', 'batchNo');
            foreach ($readyData as $youth) {
                $rgid = $youth['user']['id'];
                unset($youth['user']['id']);

                $userData = array_merge($youth['user'], [
                    'user_id' => $uuid,
                    'youth_personal_id' => $youth_personal_id,
                ]);
                $youth_user_id = DB::table('youth_users')->insertGetId($userData);

                $infoData = array_merge($youth['info'], ['youth_user_id' => $youth_user_id]);

                DB::table('youth_infos')->insert($infoData);

                if (!empty($youth['educBG']) && is_array($youth['educBG'])) {

                    foreach ($youth['educBG'] as $edu) {
                        if (is_array($edu)) {
                            foreach ($edu as $lud) {
                                DB::table('educ_b_g_s')->insert(array_merge($lud, ['youth_user_id' => $youth_user_id]));
                            }
                        }
                    }
                }
                if (!empty($youth['civic']) && is_array($youth['civic'])) {
                    foreach ($youth['civic'] as $civic) {
                        if (is_array($civic)) {
                            foreach ($civic as $lud) {
                                DB::table('civic_involvements')->insert(array_merge($lud, ['youth_user_id' => $youth_user_id]));
                            }
                        }
                    }
                }
                ValidateYouth::create([
                    'youth_user_id' => $rgid,
                    'registration_cycle_id' => $cycleID,
                ]);
            }

            // DB::rollBack();
            DB::commit();

            Bulk_logger::create([
                'user_id' => $uuid,
                'batchNo' => $batchNo,
            ]);
        } catch (\Throwable $th) {
            Log::info("inserting error: " . $th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Migration failed.',
                'error' => $th->getMessage(),
            ], 500);
        }
        $res = [
            'attempted' => $attempted,
            'submitted' => $submitted,
            'failed' => $failed,
        ];
        return response()->json($res, 200);
    }
    public function validateYouthInfoRaw(Request $request)
    {
        $request['height'] = (int)$request->input('height');
        $request['weight'] = (int)$request->input('weight');
        $request['age'] = (int)$request->input('age');

        $fields = $request->validate([
            'fname' => 'required|max:60',
            'mname' => 'required|max:60',
            'lname' => 'required|max:60',
            'sex' => 'required|in:Male,Female',
            'gender' => 'nullable|max:40',
            'age' => 'required|integer|between:15,30',
            'address' => 'required|max:100',
            'dateOfBirth' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),
            ],
            'placeOfBirth' => 'required|max:100',
            'contactNo' => 'required|max:10|min:10',
            'height' => 'nullable|integer|max:300',
            'weight' => 'nullable|integer|max:200',
            'religion' => 'required|max:100',
            'occupation' => 'nullable|max:100',
            'civilStatus' => 'required|max:100',
            'noOfChildren' => 'nullable|max:30',
            'created_at' => 'nullable|date|date_format:Y-m-d',
        ]);

        return $fields;
    }

    private function validateEducBGRaw(Request $request)
    {
        $educBg = collect($request->educBg ?? []);

        if ($educBg->filter(
            fn($item) =>
            !empty($item['level']) ||
                !empty($item['nameOfSchool']) ||
                !empty($item['periodOfAttendance'])
        )->isNotEmpty()) {
            return $request->validate([
                'educBg.*.level' => 'required|string|max:255',
                'educBg.*.nameOfSchool' => 'required|string|max:255',
                'educBg.*.periodOfAttendance' => 'required|string|max:255',
                'educBg.*.yearGraduate' => 'nullable|integer|between:1995,2100',
                'educBg.*.created_at' => 'nullable|date|date_format:Y-m-d',
            ]);
        }

        return false;
    }


    private function validateCivicInvolvementRaw(Request $request)
    {
        $civic = collect($request->civic ?? []);

        if ($civic->filter(function ($item) {
            return !empty($item['nameOfOrganization']) ||
                !empty($item['addressOfOrganization']) ||
                !empty($item['start']) ||
                !empty($item['end']) ||
                !empty($item['yearGraduated']);
        })->isNotEmpty()) {
            return $request->validate([
                'civic.*.nameOfOrganization' => 'required|string|max:255',
                'civic.*.addressOfOrganization' => 'required|string|max:255',
                'civic.*.start' => 'required|date_format:Y-m-d',
                'civic.*.end' => 'required|string|max:255',
                'civic.*.yearGraduated' => 'required|integer|between:1995,2100',
                'civic.*.created_at' => 'nullable|date|date_format:Y-m-d',
            ]);
        }

        return false;
    }


    function generateUnique7DigitCode(string $table, string $column): int
    {
        do {
            $code = mt_rand(1000000, 9999999);
        } while (DB::table($table)->where($column, $code)->exists());

        return $code;
    }





    public function youthApprove(Request $request)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $user = User::findOrFail($request->input('user_id'));
        $yUser = YouthUser::findOrFail($request->input('youthid'));
        ValidateYouth::create([
            'youth_user_id' => $yUser->id,
            'registration_cycle_id' => $cycleID,
        ]);
        $yUser->user_id =  $user->id;
        $yUser->save();
        return 'Success';
    }




    public function update(Request $request, $youth)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $youth = YouthUser::findOrFail($youth);

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
    public function destroy($youth)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        // $msg = 'Something went wrong ';
        // try {
        //     $youth->delete();
        //     $msg = 'Deleted successfylly';

        // } catch (\Exception $th) {
        //     $msg .= $th->getMessage();
        // }
        $res = '';
        try {
            $bye = YouthUser::findOrFail($youth);
            YouthUser::destroy($bye->id);
            return response()->json(['message' => 'Deleted successfylly'], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 404);
        }
    }
    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
