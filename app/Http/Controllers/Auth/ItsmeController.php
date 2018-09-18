<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Zttp\Zttp;

class ItsmeController extends Controller
{
    public function redirect()
    {
        try {
            $transaction = Zttp::post(config('services.itsme.base_url') . 'transactions', [
                'token' => config('services.itsme.token'),
                'service' => request('service', 'register'),
                'scopes' => ['profile', 'email', 'phone', 'address'],
                'locale' => 'fr',
                'redirectUrl' => route('itsme.callback', ['service' => request('service', 'register')]),
            ])->json();
        } catch (\Exception $e) {
            session()->flash('itsme_error', $e->getMessage());

            return redirect()->route(request('service'));
        }

        session()->flash('transactionToken', $transaction['transactionToken']);

        return redirect($transaction['authenticationUrl']);
    }

    public function callback()
    {
        $response = Zttp::get(config('services.itsme.base_url') . 'status/' . session('transactionToken'))->json();

        $data = [
            'name' => data_get($response, 'name.fullName'),
            'email' => data_get($response, 'emailAddress'),
            'gender' => data_get($response, 'gender'),
            'birthdate' => data_get($response, 'birthdate'),
            'locale' => data_get($response, 'locale'),
            'phone' => data_get($response, 'phoneNumber'),
            'street' => data_get($response, 'address.streetAddress'),
            'postal_code' => data_get($response, 'address.postalCode'),
            'city' => data_get($response, 'address.city'),
            'country_code' => data_get($response, 'address.countryCode'),
        ];

        if (request('service') == 'login') {
            $user = User::where('itsme_id', data_get($response, 'userId'))->first();

            $user->update($data);

            Auth::login($user, true);

            return redirect()->route('home');
        } else {
            $user = User::create(['itsme_id' => data_get($response, 'userId')] + $data);

            event(new Registered($user));

            Auth::login($user, true);

            return redirect()->route('home');
        }
    }
}
