<?php

namespace App\Http\Controllers;

use App\AccountService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class AccountController extends Controller
{

    public function auth(string $driver): RedirectResponse
    {
        try {
            $SocialiteDriver = Socialite::driver($driver);
            $SocialiteDriver->scopes(config('services.'.$driver.'.scopes'));
            $SocialiteDriver->with(['access_type' => 'offline']);
            return $SocialiteDriver->redirect();
        } catch (\InvalidArgumentException $exception) {
            report($exception);

            abort(400, $exception->getMessage());
        }
    }

    /**
     * @param string $driver
     * @return RedirectResponse
     */
    public function callback(string $driver): RedirectResponse
    {
        $token = Socialite::driver($driver)->getAccessTokenResponse(request()->get('code'));
        $userSoc = Socialite::driver($driver)->userFromToken($token['access_token']);
        $user = request()->user();
        $account = $user->accounts()->firstOrCreate([
            'driver'    =>  $driver,
            ],[
            'token'     =>  $userSoc->token,
        ]);
        $accountService = new AccountService();
        $accountService->createAccountCalendar($userSoc->token,$account);
        return redirect()->route('dashboard');
    }
}
