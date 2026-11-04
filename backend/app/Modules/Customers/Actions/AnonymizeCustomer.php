<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Events\CustomerAnonymized;
use App\Modules\Customers\Models\Customer;
use Illuminate\Support\Facades\DB;

/**
 * Eliminação a pedido do titular (LGPD art. 18), irreversível. Apaga nome, contato, documento e consentimento.
 * Ficam (ADR 0005): o cadastro anônimo com veículos, orçamentos e OS (registro da oficina) e as aprovações
 * assinadas (prova do contrato, imutáveis). Outros módulos limpam a parte deles ouvindo CustomerAnonymized.
 * Idempotente: anonimizar de novo não faz nada.
 */
final class AnonymizeCustomer
{
    public const ANONYMOUS_NAME = 'Cliente anonimizado';

    public function handle(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($customer->anonymized_at !== null) {
                return $customer;
            }

            $customer->forceFill([
                'name' => self::ANONYMOUS_NAME,
                'email' => null,
                'phone' => null,
                'phone_hash' => null,
                'document' => null,
                'document_hash' => null,
                'consent_whatsapp_at' => null,
                'consent_whatsapp_source' => null,
                'anonymized_at' => now(),
            ])->save();

            CustomerAnonymized::dispatch($customer->tenant_id, $customer->id);

            return $customer;
        });
    }
}
