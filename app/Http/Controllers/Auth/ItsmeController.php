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
        $response = Zttp::get(config('services.itsme.base_url') . 'status/' . session('transactionToken'));

        if ($response->status() == 404) {
            session()->flash('itsme_error', "The transactionToken is invalid.");

            return redirect()->route(request('service'));
        }

        if ($response->status() == 500) {
            session()->flash('itsme_error', "An error occured on the itsme server. Please try again later.");

            return redirect()->route(request('service'));
        }

        $jsonResponse = $response->json();

        switch ($jsonResponse['status']) {
            case 'cancelled':
                session()->flash('itsme_error', "The user cancelled the request.");

                return redirect()->route(request('service'));
            case 'failed':
                session()->flash('itsme_error', "The user failed the request.");

                return redirect()->route(request('service'));
            case 'expired':
                session()->flash('itsme_error', "The request expired.");

                return redirect()->route(request('service'));
            case 'success':
                $data = [
                    'name' => data_get($jsonResponse, 'name.fullName'),
                    'email' => data_get($jsonResponse, 'emailAddress'),
                    'gender' => data_get($jsonResponse, 'gender'),
                    'birthdate' => data_get($jsonResponse, 'birthdate'),
                    'locale' => data_get($jsonResponse, 'locale'),
                    'phone' => data_get($jsonResponse, 'phoneNumber'),
                    'street' => data_get($jsonResponse, 'address.streetAddress'),
                    'postal_code' => data_get($jsonResponse, 'address.postalCode'),
                    'city' => data_get($jsonResponse, 'address.city'),
                    'country_code' => data_get($jsonResponse, 'address.countryCode'),
                ];

                $user = User::where('itsme_id', data_get($jsonResponse, 'userId'))->first();

                if (request('service') == 'login') {
                    if (! $user) {
                        session()->flash('itsme_error', "These credentials does not match any existing account.");

                        return redirect()->route(request('service'));
                    }

                    $user->update($data);

                    Auth::login($user, true);

                    return redirect()->route('home');
                } else {
                    if ($user) {
                        session()->flash('itsme_error', "An account already exists with these credentials.");

                        return redirect()->route(request('service'));
                    }

                    $user = User::create(['itsme_id' => data_get($jsonResponse, 'userId')] + $data);

                    event(new Registered($user));

                    Auth::login($user, true);

                    return redirect()->route('home');
                }
            default:
                return redirect()->route(request('service'));
        }
    }
}
