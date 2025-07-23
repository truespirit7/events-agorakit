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
        $code = $request->input('code');

        // Обмен кода на токен
        $response = Http::post('https://api.vk.com/oauth/access_token', [
            'client_id' => env('VKID_CLIENT_ID'),
            'client_secret' => env('VKID_CLIENT_SECRET'),
            'redirect_uri' => env('VKID_REDIRECT_URI'),
            'code' => $code
        ]);

        $data = $response->json();
        $a = $data;
        // Получение данных пользователя
        $userInfo = Http::get('https://api.vk.com/method/users.get', [
            'user_ids' => $data['user_id'],
            'access_token' => $data['access_token'],
            'v' => '5.131',
            'fields' => 'email,phone'
        ]);

        // Здесь ваша логика работы с пользователем
        // Например, создание/авторизация пользователя
        
        return response()->json([
            'access_token' => $data['access_token'],
            'user' => $userInfo->json()
        ]);

    }

    public function handleVKUserInfo(Request $request)
    {
        $data = $request->input('user');
        $userInfo = $data['user'];
        if(!empty($userInfo['email'])) {
            $user = User::where('email', $userInfo['email'])->first();
            if ($user) {
                Auth::login($user);
                return json_encode(["status" => "exists"]);
            }else {
                // Create a new user if not exists
                $user = User::create([
                    'name' => $userInfo['first_name'] . ' ' . $userInfo['last_name'],
                    'email' => $userInfo['email'],
                    'password' => bcrypt(Str::random(16)), // Random password
                ]);
                Auth::login($user);
                return json_encode(["status" => "created"]);
            }
        }
    }
}
