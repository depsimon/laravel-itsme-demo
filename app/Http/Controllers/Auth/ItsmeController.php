<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Zttp\Zttp;

class ItsmeController extends Controller
{
    public function redirect()
    {
        $transaction = Zttp::post(config('services.itsme.base_url') . 'transactions', [
            'token' => config('services.itsme.token'),
            'service' => request('service', 'register'),
            'scopes' => ['profile', 'email', 'phone', 'address'],
            'locale' => 'fr',
            'redirectUrl' => route('itsme.callback', ['service' => request('service', 'register')]),
        ])->json();

        session()->flash('transactionToken', $transaction['transactionToken']);

        return redirect($transaction['authenticationUrl']);
    }
}
