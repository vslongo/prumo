<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Actions\AnonymizeCustomer;
use App\Modules\Customers\Actions\CreateCustomer;
use App\Modules\Customers\Actions\ExportCustomerData;
use App\Modules\Customers\Actions\SearchCustomers;
use App\Modules\Customers\Actions\UpdateCustomer;
use App\Modules\Customers\Data\CustomerData;
use App\Modules\Customers\Http\Requests\StoreCustomerRequest;
use App\Modules\Customers\Http\Requests\UpdateCustomerRequest;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Customers\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class CustomerController
{
    public function index(Request $request, SearchCustomers $search): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Customer::class);

        return CustomerResource::collection($search->handle($request->string('q')->toString()));
    }

    public function store(StoreCustomerRequest $request, CreateCustomer $create): CustomerResource
    {
        Gate::authorize('create', Customer::class);

        return new CustomerResource($create->handle(CustomerData::fromArray($request->validated())));
    }

    public function show(Customer $customer): CustomerResource
    {
        Gate::authorize('view', $customer);

        return new CustomerResource($customer);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer, UpdateCustomer $update): CustomerResource
    {
        Gate::authorize('update', $customer);

        /** @var array{name?: string, email?: string|null, phone?: string|null, document?: string|null, consent_whatsapp?: bool, consent_source?: string|null} $input */
        $input = $request->validated();

        return new CustomerResource($update->handle($customer, $input));
    }

    public function export(Customer $customer, ExportCustomerData $export): JsonResponse
    {
        Gate::authorize('export', $customer);

        return response()->json($export->handle($customer), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="cliente-'.$customer->id.'.json"');
    }

    public function anonymize(Customer $customer, AnonymizeCustomer $anonymize): CustomerResource
    {
        Gate::authorize('anonymize', $customer);

        return new CustomerResource($anonymize->handle($customer));
    }
}
