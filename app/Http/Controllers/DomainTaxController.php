<?php

namespace App\Http\Controllers;

use App\Models\PaymentTaxes;

class DomainTaxController extends Controller
{
    public function index()
    {
        $taxes = PaymentTaxes::all();

        return response()->json($taxes);
    }
}
