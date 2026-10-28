<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Actions;

use App\Modules\Customers\Models\Customer;
use App\Shared\Support\BrazilPhone;

/**
 * Para quem o sistema pode mandar WhatsApp sozinho: cliente com celular e consentimento registrado
 * (mensagem iniciada pela empresa exige opt-in; ADR 0003). Sem isso, null e o técnico usa o wa.me.
 */
final class FindConsentingRecipient
{
    /** @return array{to: string, name: string, customer_id: string}|null */
    public function handle(?Customer $customer): ?array
    {
        $to = BrazilPhone::e164Digits($customer?->phone);
        if ($customer === null || $to === null || $customer->consent_whatsapp_at === null) {
            return null;
        }

        return ['to' => $to, 'name' => $customer->name, 'customer_id' => $customer->id];
    }
}
