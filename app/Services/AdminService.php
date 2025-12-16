<?php
namespace App\Services;

use App\Repositories\Contracts\CitizenRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;


use Exception;

class AdminService
{
 protected $repo;

    public function __construct(CitizenRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }
    public function login(string $mobile, string $password)
    {
        $user = $this->repo->findByMobile1($mobile);

        if (!$user || !Hash::check($password, $user->password)) {
            return ['ok' => false, 'message' => 'Invalid mobile number or password.'];
        }

        // if (!$user->is_verified) {
        //     return ['ok' => false, 'message' => 'Account not verified. Please verify your mobile number first.'];
        // }

        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'ok' => true,
            'user' => $user,
            'token' => $token
        ];
    }
    public function logout1($user)
    {

          if (!$user) {
              return [
                 'status' => 401,
                 'message' => 'user not authenticated.'
            ];
        }
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return [
            'status' => 200,
           'message' => 'Logged out successfully.'
        ];
    }   
}