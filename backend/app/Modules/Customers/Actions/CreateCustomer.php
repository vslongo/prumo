<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Data\CustomerData;
use App\Modules\Customers\Events\CustomerCreated;
use App\Modules\Customers\Models\Customer;
use App\Shared\Support\BlindIndex;

final class CreateCustomer
{
    public function handle(CustomerData $data): Customer
    {
        $phone = BlindIndex::digits($data->phone);
        $document = BlindIndex::digits($data->document);

        $customer = Customer::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $phone,
            'phone_hash' => BlindIndex::hash($phone),
            'document' => $document,
            'document_hash' => BlindIndex::hash($document),
            'consent_whatsapp_at' => $data->consentWhatsapp ? now() : null,
            'consent_whatsapp_source' => $data->consentWhatsapp ? ($data->consentSource ?? 'balcao') : null,
        ]);

        CustomerCreated::dispatch($customer->tenant_id, $customer->id);

        return $customer;
    }
}
