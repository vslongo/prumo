<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\Customer;
use App\Shared\Support\BlindIndex;
use Illuminate\Validation\ValidationException;

/**
 * Edição parcial do cliente. Consentimento de WhatsApp: marcar grava data e origem (se ainda não tinha);
 * desmarcar é a revogação e apaga os dois. Cliente anonimizado não volta a ter dados.
 */
final class UpdateCustomer
{
    /** @param array{name?: string, email?: string|null, phone?: string|null, document?: string|null, consent_whatsapp?: bool, consent_source?: string|null} $input */
    public function handle(Customer $customer, array $input): Customer
    {
        if ($customer->anonymized_at !== null) {
            throw ValidationException::withMessages(['customer' => 'Cliente anonimizado não pode ser editado.']);
        }

        if (array_key_exists('name', $input)) {
            $customer->name = $input['name'];
        }
        if (array_key_exists('email', $input)) {
            $customer->email = $input['email'];
        }
        if (array_key_exists('phone', $input)) {
            $customer->phone = BlindIndex::digits($input['phone']);
            $customer->setAttribute('phone_hash', BlindIndex::hash($customer->phone));
        }
        if (array_key_exists('document', $input)) {
            $customer->document = BlindIndex::digits($input['document']);
            $customer->setAttribute('document_hash', BlindIndex::hash($customer->document));
        }

        if (array_key_exists('consent_whatsapp', $input)) {
            if (! $input['consent_whatsapp']) {
                $customer->consent_whatsapp_at = null;
                $customer->consent_whatsapp_source = null;
            } elseif ($customer->consent_whatsapp_at === null) {
                $customer->consent_whatsapp_at = now();
                $customer->consent_whatsapp_source = $input['consent_source'] ?? 'balcao';
            }
        }

        $customer->save();

        return $customer;
    }
}
