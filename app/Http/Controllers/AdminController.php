<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CitizenLoginRequest;
use App\Http\Requests\CitizenLogoutRequest;
use App\Services\AdminService;
class AdminController extends Controller
{
      protected $service;
    public function __construct(AdminService $service)
    {
        $this->service = $service;
    }
      public function login1(CitizenLoginRequest $request)
    {
        $res = $this->service->login($request->mobile, $request->password);

        if (!$res['ok']) {
            return response()->json(['message' => $res['message']], 401);
        }

        return response()->json([
            'message' => 'Login successfully.',
            'user' => [
                'id' => $res['user']->id,
                'username' => $res['user']->username,
                'mobile' => $res['user']->mobile,
                //'is_verified' => (bool)$res['user']->is_verified,
            ],
            'access_token' => $res['token'],
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout1(Request $request)
{
    $citizen = $request->user(); 
    $res = $this->service->logout1($citizen); 

    return response()->json([
        'message' => $res['message']
    ], $res['status']);
}



}
