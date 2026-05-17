<?php
namespace App\Http\Controllers\Settings;
use App\Models\PaymentTender;
use Inertia\Inertia;
use Inertia\Response;

class PaymentTenderSettingsController extends \App\Http\Controllers\Controller {
    public function index(): Response {
        return Inertia::render('settings/PaymentTenders', [
            'tenders' => PaymentTender::orderBy('display_order')->orderBy('name')->get(),
        ]);
    }
}
