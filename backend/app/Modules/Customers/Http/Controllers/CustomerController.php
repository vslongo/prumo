<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Controllers;

use App\Modules\Customers\Actions\CreateCustomer;
use App\Modules\Customers\Actions\SearchCustomers;
use App\Modules\Customers\Data\CustomerData;
use App\Modules\Customers\Http\Requests\StoreCustomerRequest;
use App\Modules\Customers\Http\Resources\CustomerResource;
use App\Modules\Customers\Models\Customer;
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
}
