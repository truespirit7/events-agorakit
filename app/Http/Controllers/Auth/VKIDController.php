<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class VKIDController extends Controller
{


    public function callback(Request $request) {

    }

    public function handleVKUserInfo(Request $request)
    {
        $data = $request->input('user');
        $userInfo = $data['user'];
        if(!empty($userInfo['email'])) {
            $user = User::where('email', $userInfo['email'])->first();
            if ($user) {
                Auth::login($user);
                return "exists";
            }else {
                // Create a new user if not exists
                $user = User::create([
                    'name' => $userInfo['first_name'] . ' ' . $userInfo['last_name'],
                    'email' => $userInfo['email'],
                    'password' => bcrypt(Str::random(16)), // Random password
                ]);
                Auth::login($user);
                return "created";
            }
        }
    }
}
