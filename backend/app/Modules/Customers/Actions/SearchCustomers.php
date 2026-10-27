<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\Customer;
use App\Shared\Support\BlindIndex;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Busca por nome (parcial) ou por telefone/CPF exatos via hash. */
final class SearchCustomers
{
    /** @return LengthAwarePaginator<int, Customer> */
    public function handle(?string $term): LengthAwarePaginator
    {
        $term = trim((string) $term);
        $hash = BlindIndex::hash($term);

        return Customer::query()
            ->when($term !== '', fn ($q) => $q->where(function ($q) use ($term, $hash) {
                $q->where('name', 'ilike', '%'.addcslashes($term, '%_\\').'%');
                if ($hash !== null) {
                    $q->orWhere('phone_hash', $hash)->orWhere('document_hash', $hash);
                }
            }))
            ->orderBy('name')
            ->paginate(20);
    }
}
