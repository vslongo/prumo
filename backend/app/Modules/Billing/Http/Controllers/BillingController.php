<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Modules\Billing\Actions\CancelSubscription;
use App\Modules\Billing\Actions\ChangePlan;
use App\Modules\Billing\Actions\GetBillingOverview;
use App\Modules\Billing\Actions\ResolveTenantAccess;
use App\Modules\Billing\Actions\Subscribe;
use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Http\Requests\ChangePlanRequest;
use App\Modules\Billing\Http\Requests\SubscribeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Assinatura do SaaS. Só o dono gerencia; a situação de acesso (avisos de trial/atraso) todos veem. */
final class BillingController
{
    public function access(ResolveTenantAccess $access): JsonResponse
    {
        return response()->json(['data' => $access->handle()->toArray()]);
    }

    public function show(GetBillingOverview $overview): JsonResponse
    {
        Gate::authorize('manage-billing');

        return response()->json(['data' => $overview->handle()]);
    }

    public function subscribe(SubscribeRequest $request, Subscribe $subscribe, GetBillingOverview $overview): JsonResponse
    {
        Gate::authorize('manage-billing');
        $subscribe->handle(Plan::from($request->string('plan')->toString()), $request->string('cpf_cnpj')->toString(), (string) $request->user()?->getAttribute('email'));

        return response()->json(['data' => $overview->handle()], 202);
    }

    public function changePlan(ChangePlanRequest $request, ChangePlan $change, GetBillingOverview $overview): JsonResponse
    {
        Gate::authorize('manage-billing');
        $change->handle(Plan::from($request->string('plan')->toString()));

        return response()->json(['data' => $overview->handle()]);
    }

    public function cancel(Request $request, CancelSubscription $cancel, GetBillingOverview $overview): JsonResponse
    {
        Gate::authorize('manage-billing');
        $cancel->handle();

        return response()->json(['data' => $overview->handle()]);
    }
}
