<?php

namespace App\Http\Controllers;

use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'userName' => 'required|max:100|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
            'name' => 'required|max:100',
            'position' => 'required|max:100',
        ]);
        $fields = $request->validate([
            'userName' => 'required|max:100|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);
        $skofficialInfo = $request->validate([
            'name' => 'required|max:100',
            'position' => 'required|max:100',
        ]);

        $user = User::create($fields);

        $skofficialInfo['user_id'] = $user->id;

        SkOfficial::create($skofficialInfo);


        return 1;
    }

    public function modifyUser(Request $request, SkOfficial $offid)
    {
        $skid = SkOfficial::findOrFail($request->input('id'));
        $user = User::findOrFail($request->input('user_id'));
        $rules = [
            'userName' => [
                'required',
                'max:100',
                Rule::unique('users')->ignore($user->id)
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'name' => 'required|max:100',
            'position' => 'required|max:100',
        ];
        if ($request->filled('password')) {
            $rules['password'] = 'required|min:8|confirmed';
        }

        $vd = $request->validate($rules);
        $user->userName = $vd['userName'];
        $user->email = $vd['email'];
        if (!empty($vd['password'])) {
            $user->password = bcrypt($vd['password']);
        }
        $skid->position = $vd['position'];
        $skid->name = $vd['name'];
        $isdt = false;
        if ($user->isDirty()) {
            $user->save();
            $isdt = true;
        }
        if ($skid->isDirty()) {
            $skid->save();
            $isdt = true;
        }

        return [
            'msg' => $isdt ? 'updated successfully...' : 'no changes made!',
            'isDirty' => $isdt,
        ];
    }




    public function loginAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'errors' => [
                    'password' => 'Incorrect password.'
                ]
            ], 422);
        }

        if ($user->role !== 'Admin') {
            return response()->json([
                'errors' => [
                    'auth' => 'Unauthorized access.'
                ]
            ], 403);
        }

        Auth::login($user);

        $request->session()->regenerate();

        $user = $user->load('admin');

        return response()->json([
            'user' => $user
        ]);
    }


    public function loginOfficials(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        $errors = [];

        if (!Hash::check($request->password, $user->password)) {
            $errors['password'] = 'Incorrect Password.';
        }

        if ($user->role !== 'SKOfficial') {
            $errors['auth'] = 'Unauthorized';
        }

        if (!empty($errors)) {
            return response()->json(['errors' => $errors], 422);
        }
        $token = $user->createToken($user->userName);

        $user->tokens()->latest()->first()->forceFill([
            'expires_at' => now()->addHour(),
        ])->save();

        return [
            'token' => $token->plainTextToken,
        ];
    }



    public function searchSkOfficial(Request $request)
    {
        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);


        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $results = SkOfficial::where(function ($query) use ($search) {
            $query->where('name', 'LIKE', '%' . $search . '%');
        })
            ->orderBy('name', "DESC")
            ->with([
                'user',
            ])
            ->withCount('insertedYouth')
            ->paginate($perPage)
            ->appends(['search' => $search]);

        $pass = $results->map(function ($info) {
            return [
                'skofficial' => [
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



    public function getUser(Request $request)
    {
        $user = $request->user();

        $ch = $user->admin ?? $user->skofficials;
        return [
            'user' => $user,
            'ch' => $ch,
        ];
    }





    public function destroy(Request $request, SkOfficial $id)
    {
        // $msg = 'Something went wrong ';
        // try {
        //     $youth->delete();
        //     $msg = 'Deleted successfylly';

        // } catch (\Exception $th) {
        //     $msg .= $th->getMessage();
        // }
        // $youth->delete();
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => $user], 401);
        }

        YouthUser::where('id', $id->id)->update(['user_id' => $user->id]);
        $id->user()->delete();

        return response()->json(['message' => 'Deleted successfylly']);
    }
}
