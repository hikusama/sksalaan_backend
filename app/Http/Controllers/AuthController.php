<?php

namespace App\Http\Controllers;

use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Hash;

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
            'password' => 'required|confirmed',
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

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'email' => [
                        'The provided credentials are incorrect.'
                    ]
                ]
            ];
        }

        $token = $user->createToken($user->userName);

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
            ->orderBy('name', "ASC")
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



    public function getUserById($id)
    {
        $user = User::with(['skofficials'])->find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $ch = $user->admin ?? $user->skofficials;

        return response()->json([
            'user' => $user,
            'ch' => $ch,
        ]);
    }

    public function destroy(Request $request,SkOfficial $id)
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
