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
        $cid = $this->getCycle();
        $cy = null;

        // Initialize variables
        $validity = 'all';
        $youthType = '';
        $sex = '';
        $gender = '';
        $civilStatus = '';
        $ageType = null;
        $ageValue = [];
        $qualification = 'All';
        $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';

        if ($typeId) {
            $validity = strtolower($request->input('validity', 'All'));
            $youthType = strtolower($request->input('youthType'));
            $sex = strtolower($request->input('sex'));
            $gender = strtolower($request->input('gender'));
            $civilStatus = strtolower($request->input('civilStatus'));
            $ageType = $request->input('ageType');
            $ageValue = $request->input('ageValue', []);
            $qualification = strtolower($request->input('qualification', 'All'));

            if ($cycleID !== 'all') {
                $cy = RegistrationCycle::findOrFail($cycleID);
            }
        }

        $allowedFilters = ['fname', 'lname', 'age', 'created_at'];
        if (!in_array($sortBy, $allowedFilters)) {
            return response()->json(['error' => 'Invalid filter field'], 400);
        }

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $query = YouthInfo::select('youth_infos.*')
            ->where(function ($query) use ($search) {
                $query->where('fname', 'LIKE', '%' . $search . '%')
                    ->orWhere('mname', 'LIKE', '%' . $search . '%')
                    ->orWhere('lname', 'LIKE', '%' . $search . '%')
                    ->orWhereHas('yUser', function ($q) use ($search) {
                        $q->where('batchNo', $search)
                            ->orWhere('youth_personal_id', $search);
                    });
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
            ->when(!empty($sex), function ($q) use ($sex) {
                $q->whereRaw('LOWER(TRIM(sex)) = ?', [$sex]);
            })
            ->when(!empty($gender), function ($q) use ($gender) {
                $q->whereRaw('LOWER(TRIM(gender)) = ?', [$gender]);
            })
            ->when(!empty($civilStatus), function ($q) use ($civilStatus) {
                $q->whereRaw('LOWER(TRIM(civilStatus)) = ?', [$civilStatus]);
            });

        $query->whereHas('yUser', function ($sub) use ($youthType) {
            if (!empty($youthType)) {
                $sub->whereRaw('LOWER(TRIM(youthType)) = ?', [$youthType]);
            }
        });

        if ($typeId) {
            if ($validity === 'all') {
                if ($cy != null) {
                    $query->when($cy->cycleStatus === 'inactive', function ($qq) use ($cycleID) {
                        $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                            $q->where('registration_cycle_id', $cycleID);
                        });
                    });
                }
            } else {
                if ($validity === 'validated') {
                    $query->where(function ($sub) use ($cid) {
                        $sub->whereHas('yUser.validated', function ($qq) use ($cid) {
                            $qq->where('registration_cycle_id', $cid);
                        });
                    });
                } elseif ($validity === 'unvalidated') {
                    $query->whereDoesntHave('yUser.validated', function ($qq) use ($cid) {
                        $qq->where('registration_cycle_id', $cid);
                    });
                }
            }
            $query->when(true, function ($q) use ($qualification, $ageType, $ageValue, $ageExpression) {
                if ($qualification === 'unqualified') {
                    $q->where(function ($sub) use ($ageExpression) {
                        $sub->where(DB::raw($ageExpression), '<', 15)
                            ->orWhere(DB::raw($ageExpression), '>', 30);
                    });
                } elseif ($qualification === 'qualified') {
                    if ($ageType === 'single' && !empty($ageValue['min'])) {
                        $q->whereRaw("$ageExpression = ?", [intval($ageValue['min'])]);
                    } elseif ($ageType === 'range' && !empty($ageValue['min']) && !empty($ageValue['max'])) {
                        $q->whereBetween(DB::raw($ageExpression), [
                            intval($ageValue['min']),
                            intval($ageValue['max'])
                        ]);
                    } else {
                        $q->whereBetween(DB::raw($ageExpression), [15, 30]);
                    }
                }
            });
        }

        // Handle sorting by age without selecting it
        if ($sortBy === 'age') {
            $query->orderByRaw("$ageExpression ASC");
        } else {
            $query->orderBy($sortBy, 'ASC');
        }

        // Paginate first to avoid GROUP BY errors
        $results = $query->paginate($perPage)->appends($request->all());

        // Load relationships and add is_validated after pagination
        $results->getCollection()->load([
            'yUser',
            'yUser.educbg',
            'yUser.civicInvolvement'
        ])->transform(function ($info) use ($cid) {
            $info->is_validated = DB::table('validated_youths')
                ->where('youth_user_id', $info->youth_user_id)
                ->where('registration_cycle_id', $cid)
                ->exists();
            return $info;
        });

        $pass = $results->map(function ($info) {
            $arr = $info->toArray();
            $arr['is_validated'] = (bool) $info->is_validated;
            return [
                'youthUser' => [$arr]
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

    private function validateYouthInfo(Request $request, $youth = null)
    {

        $fields = $request->validate([
            'firstname' => 'required|max:60',
            'middlename' => 'required|max:60',
            'lastname' => [
                'required',
                'max:60',
                function ($attribute, $value, $fail) use ($request, $youth) {
                    $query = YouthInfo::whereRaw(
                        'LOWER(fname) = ? AND LOWER(mname) = ? AND LOWER(lname) = ?',
                        [strtolower($request->firstname), strtolower($request->middlename), strtolower($request->lastname)]
                    );

                    if ($youth) {
                        $query->where('youth_user_id', '!=', $youth->id);
                    }

                    $exists = $query->exists();

                    if ($exists) {
                        $fail('A youth with the same full name already exists.');
                    }
                },
            ],
            'sex' => 'required|in:Male,Female',
            'gender' => 'nullable|max:40',
            'address' => 'required|max:100',
            'dateOfBirth' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),
            ],
            'placeOfBirth' => 'required|max:100',
            'contactNo' => 'required|regex:/^09\d{9}$/',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:200',
            'religion' => 'required|max:100',
            'occupation' => 'nullable|max:100',
            'civilStatus' => 'required|max:100',
            'noOfChildren' => 'nullable|integer|min:0|max:30',
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


    private function validateYouthValidate(Request $request)
    {

        $fields = $request->validate([
            'gender' => 'nullable|max:40',
            'address' => 'required|max:100',
            'contactNo' => 'required|regex:/^09\d{9}$/',
            'noOfChildren' => 'nullable|integer|min:0|max:30',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:200',
            'civilStatus' => 'required|max:100',
            'occupation' => 'nullable|max:100',
            'religion' => 'required|max:100',
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

        $readyData = [];
        $ex = [];
        $regs = [];
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
                try {
                    $infoRequest->validate([
                        'fname' => 'required|max:60',
                        'mname' => 'required|max:60',
                        'lname' => [
                            'required',
                            'max:60',
                            function ($attribute, $value, $fail) use ($infoRequest) {
                                $exists = YouthInfo::whereRaw(
                                    'LOWER(fname) = ? AND LOWER(mname) = ? AND LOWER(lname) = ?',
                                    [strtolower($infoRequest->fname), strtolower($infoRequest->mname), strtolower($infoRequest->lname)]
                                )->exists();

                                if ($exists) {
                                    $fail('A youth with the same full name already exists.');
                                }
                            },
                        ],
                    ]);
                } catch (\Throwable $th) {
                    array_push($ex, $data['user']['id']);
                    continue;
                }

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

                // $submitted[] = $data['user']['id'] ?? null;
            } catch (\Throwable $th) {
                Log::info("error validation" . $th->getMessage());

                // $failed[] = $data['user']['id'] ?? null;
            }
        }
        $uuid = $request->user()->id;

        // $uuid = 2;
        DB::beginTransaction();
        try {
            $youth_personal_id = $this->generateUnique7DigitCode('youth_users', 'youth_personal_id');

            $batchNo = $this->generateUnique7DigitCode('youth_users', 'batchNo');
            foreach ($readyData as $youth) {
                // $rgid = $youth['user']['id'];
                unset($youth['user']['id']);

                $userData = array_merge($youth['user'], [
                    'user_id' => $uuid,
                    'youth_personal_id' => $youth_personal_id,
                    'batchNo' => $batchNo,
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

                array_push($regs, $youth_user_id);
                ValidateYouth::create([
                    'youth_user_id' => $youth_user_id,
                    'registration_cycle_id' => $cycleID,
                ]);
            }

            // DB::rollBack();
            DB::commit();
            if (count($regs) > 0) {
                Bulk_logger::create([
                    'user_id' => $uuid,
                    'batchNo' => $batchNo,
                ]);
            }
        } catch (\Throwable $th) {
            Log::info("inserting error: " . $th->getMessage());
            DB::rollBack();
            return response()->json([
                'message' => 'Migration failed.',
                'error' => $th->getMessage(),
            ], 500);
        }

        $res = [
            'regs' => $regs,
            'ex' => $ex,
        ];
        return response()->json($res, 200);
    }
    public function validateYouthInfoRaw(Request $request)
    {
        $fields = $request->validate([
            'fname' => 'required|max:60',
            'mname' => 'required|max:60',
            'lname' => [
                'required',
                'max:60',
                function ($attribute, $value, $fail) use ($request) {
                    $exists = YouthInfo::whereRaw(
                        'LOWER(fname) = ? AND LOWER(mname) = ? AND LOWER(lname) = ?',
                        [strtolower($request->fname), strtolower($request->mname), strtolower($request->lname)]
                    )->exists();

                    if ($exists) {
                        $fail('A youth with the same full name already exists.');
                    }
                },
            ],
            'sex' => 'required|in:Male,Female',
            'gender' => 'nullable|max:40',
            'address' => 'required|max:100',
            'dateOfBirth' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:' . now()->subYears(15)->format('Y-m-d'),
                'after_or_equal:' . now()->subYears(30)->format('Y-m-d'),
            ],
            'placeOfBirth' => 'required|max:100',
            'contactNo' => 'required|regex:/^09\d{9}$/',
            'height' => 'nullable|integer|min:0|max:300',
            'weight' => 'nullable|integer|min:0|max:200',
            'religion' => 'required|max:100',
            'occupation' => 'nullable|max:100',
            'civilStatus' => 'required|max:100',
            'noOfChildren' => 'nullable|integer|min:0|max:30',
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
        $type = $request->input('mfy', true);

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $youth = YouthUser::findOrFail($youth);

        $fields = $request->validate([
            'youthType' => 'required|max:255',
            'skillsf' => 'required|max:100',
        ]);

        if ($type) {
            $fields2 = $this->validateYouthInfo($request, $youth);
        } else {
            $fields2 = $this->validateYouthValidate($request);
        }

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
        if (!$type) {
            ValidateYouth::create([
                'youth_user_id' => $youth->id,
                'registration_cycle_id' => $cycleID,
            ]);
        }
        return response()->json(['message' => $type ? $msg : 'Validated successfully...']);
    }



    public function validateFromMobile(Request $request)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }


        // $results = [];
        $uv = [];
        $val = [];
        $fail = [];
        $nf = [];
        $res = 200;

        foreach ($request->all() as $item) {
                Log::info('56', ['idM' => $item['user']['idM']]);

            try {

                // --- VALIDATE ---
                $userFields = Validator::make($item['user'] ?? [], [
                    'id'        => 'required|integer',
                    'youthType' => 'required|string|max:255',
                    'skills'    => 'required|string|max:100',
                    'created_at' => 'nullable|date',
                ])->validate();

                $infoFields = Validator::make($item['info'] ?? [], [
                    'fname'        => 'required|string|max:255',
                    'mname'        => 'nullable|string|max:255',
                    'lname'        => 'required|string|max:255',
                    'sex'          => 'nullable|string|max:20',
                    'gender'       => 'nullable|string|max:20',
                    'address'      => 'required|string|max:100',
                    'dateOfBirth'  => 'required|date',
                    'placeOfBirth' => 'nullable|string|max:100',
                    'contactNo'    => 'required|regex:/^09\d{9}$/',
                    'height'       => 'nullable|integer|min:0|max:300',
                    'weight'       => 'nullable|integer|min:0|max:200',
                    'religion'     => 'required|string|max:100',
                    'occupation'   => 'nullable|string|max:100',
                    'civilStatus'  => 'required|string|max:100',
                    'noOfChildren' => 'nullable|integer|min:0|max:30',
                    'created_at'   => 'nullable|date',
                ])->validate();

                $educFields = Validator::make(['educBG' => $item['educBG'] ?? []], [
                    'educBG' => 'nullable|array',
                    'educBG.*.id' => 'nullable|integer',
                    'educBG.*.level' => 'required|string|max:255',
                    'educBG.*.nameOfSchool' => 'required|string|max:255',
                    'educBG.*.periodOfAttendance' => 'required|string|max:255',
                    'educBG.*.yearGraduate' => 'nullable|string|max:20',
                    'educBG.*.created_at' => 'nullable|date',
                ])->validate();

                $civicFields = Validator::make(['civic' => $item['civic'] ?? []], [
                    'civic' => 'nullable|array',
                    'civic.*.id' => 'nullable|integer',
                    'civic.*.nameOfOrganization' => 'required|string|max:255',
                    'civic.*.addressOfOrganization' => 'required|string|max:255',
                    'civic.*.start' => 'required|string|max:50',
                    'civic.*.end' => 'nullable|string|max:50',
                    'civic.*.yearGraduated' => 'nullable|string|max:20',
                    'civic.*.created_at' => 'nullable|date',
                ])->validate();

                // --- FETCH EXISTING USER ---
                $youth = YouthUser::find($userFields['id']);
                if (!$youth) {
                    array_push($nf, $item['user']['idM']);
                    continue;
                }

                $youth->load('info', 'educbg', 'civicInvolvement', 'validated');
                $existingValidation = ValidateYouth::where('youth_user_id', $youth->id)
                    ->where('registration_cycle_id', $cycleID)
                    ->exists();
                if ($existingValidation) {
                    array_push($val, $item['user']['idM']);
                    continue;
                }
                DB::beginTransaction();


                // --- UPDATE USER ---
                $youth->fill($userFields);
                if ($youth->isDirty()) {
                    $youth->save();
                }

                // --- UPDATE INFO ---
                if ($youth->info) {
                    $youth->info->fill($infoFields);
                    if ($youth->info->isDirty()) {
                        $youth->info->save();
                    }
                }

                // --- UPDATE EDUCATION ---
                $educIds = collect($educFields['educBG'] ?? [])->pluck('id')->filter();
                $youth->educbg()->whereNotIn('id', $educIds)->delete();

                foreach ($educFields['educBG'] ?? [] as $eduData) {
                    if (!empty($eduData['id'])) {
                        $educ = $youth->educbg()->find($eduData['id']);
                        if ($educ) {
                            $educ->fill($eduData);
                            if ($educ->isDirty()) {
                                $educ->save();
                            }
                        }
                    } else {
                        $youth->educbg()->create($eduData);
                    }
                }

                // --- UPDATE CIVIC ---
                $civicIds = collect($civicFields['civic'] ?? [])->pluck('id')->filter();
                $youth->civicInvolvement()->whereNotIn('id', $civicIds)->delete();

                foreach ($civicFields['civic'] ?? [] as $civicData) {
                    if (!empty($civicData['id'])) {
                        $civic = $youth->civicInvolvement()->find($civicData['id']);
                        if ($civic) {
                            $civic->fill($civicData);
                            if ($civic->isDirty()) {
                                $civic->save();
                            }
                        }
                    } else {
                        $youth->civicInvolvement()->create($civicData);
                        $changed = true;
                    }
                }
                $youth->validated()->update(['registration_cycle_id' => $cycleID]);
                array_push($uv, $item['user']['idM']);

                DB::commit();
                $res = 200;
            } catch (\Throwable $e) {
                array_push($fail, $item['user']['idM']);
                Log::info('error0909', ['exception' => $e->getMessage()]);
                DB::rollBack();
                $res = 400;
            }
        }


        return response()->json([
            'nf' => $nf,
            'uv' => $uv,
            'fail' => $fail,
            'val' => $val,
            'message' => 'Bulk validation success.',
        ],$res);
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
