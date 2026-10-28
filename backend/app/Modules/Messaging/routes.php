<?php

declare(strict_types=1);

use App\Modules\Messaging\Http\Controllers\DeliveryStatusController;
use App\Modules\Messaging\Http\Middleware\VerifyWorkerSignature;
use Illuminate\Support\Facades\Route;

// Só o worker WhatsApp chama (servidor a servidor, assinado). Sem sessão nem tenant de usuário.
Route::post('internal/whatsapp/status', DeliveryStatusController::class)->middleware(VerifyWorkerSignature::class);
