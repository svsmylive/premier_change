<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\RestorePasswordOnEmailRequest;
use App\Mail\EmailConfirmationMail;
use App\Mail\RestorePasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController
{
    public function login(LoginRequest $request)
    {
        $request->authenticate();
        $request->session()->regenerate();

        return response()->json(["success" => true, "message" => "Успешно!", "redirect" => "/account"]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'Успешно!']);
    }

    public function passwordReset(RestorePasswordOnEmailRequest $request)
    {
        $password = Str::random(12);
        $hash = Hash::make($password);
        $mail = $request->login;

        DB::transaction(function () use ($mail, $hash, $password) {
            $user = User::getByEmail($mail)->first();

            if (!$user) {
                return response()->json(
                    ['success' => false, 'message' => 'Пользователь с таким email не существует']
                );
            }

            $user->password = $hash;
            $user->save();

            Mail::to($mail)->send(new RestorePasswordMail($password));
        });

        return response()->json(['success' => true, 'message' => 'Пароль отправлен на указнную почту']);
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = new User();
        $hash = Str::random(40);

        $user->login = $data['login'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->is_confirmed = false;
        $user->email_code = $hash;
        $user->save();

        Mail::to($user->email)->send(new EmailConfirmationMail($hash));

        return response()->json(['success' => true, 'message' => 'Проверочный код отправлен на почту']);
    }

    public function confirmEmail(Request $request)
    {
        $code = $request->code;
        $user = User::byCode($code)->first();

        if ($user) {
            $user->is_confirmed = true;
            $user->save();

            return response()->json(['success' => true, 'message' => 'Успешно!']);
        }

        return response()->json(['success' => false, 'message' => 'Неверный код']);
    }

    public function account()
    {
        $user = auth()->user();
        $user->load(['referrals', 'orders']);

        $orders = [];
        $referrals = [];

        foreach ($user->orders as $order) {
            $orders[] = [
                'number' => $order->id,
                'date' => $order->created_at->timezone('Europe/Moscow')->format('d.m.Y H:i'),
                'course_from' => $order->course_from,
                'course_to' => $order->course_to,
                'valute_from' => $order->valute_from,
                'valute_to' => $order->valute_to,
                'amount_from' => $order->sum_from,
                'amount_to' => $order->sum_to,
//                'status' => 'В процессе', //todo
            ];
        }

        foreach ($user->referrals as $referral) {
            $referrals[] = [
                'number' => $referral->id,
                'date' => $referral->created_at->timezone('Europe/Moscow')->format('d.m.Y H:i'),
                'requisite' => $referral->requisite,
                'amount' => $referral->amount,
//                'status' => 'В процессе', //todo
            ];
        }

        return [
            'referral_total' => 0,  //todo
            'referral_received' => 0,  //todo
            'referral_in_process' => 0,  //todo
            'referral_percent' => $user->ref_percent,
            'referral_link' => 'https://bit-prime.ru/?rid=' . $user->id,
            'balance' => $user->balance, //todo
            'referrals' => $referrals,
            'orders' => $orders,
        ];
    }
}
