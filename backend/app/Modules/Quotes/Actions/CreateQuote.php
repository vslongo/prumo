<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Data\CreateQuoteData;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Vehicles\Actions\VehicleBelongsToCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateQuote
{
    public function __construct(
        private readonly AddQuoteItem $addItem,
        private readonly VehicleBelongsToCustomer $vehicleBelongsToCustomer,
    ) {}

    public function handle(CreateQuoteData $data): Quote
    {
        if (! $this->vehicleBelongsToCustomer->handle($data->vehicleId, $data->customerId)) {
            throw ValidationException::withMessages(['vehicle_id' => 'O veículo não pertence a esse cliente.']);
        }

        return DB::transaction(function () use ($data) {
            $quote = Quote::create([
                'customer_id' => $data->customerId,
                'vehicle_id' => $data->vehicleId,
                'created_by' => $data->createdBy,
                'status' => QuoteStatus::Draft,
                'mileage' => $data->mileage,
                'notes' => $data->notes,
            ]);

            foreach ($data->items as $i => $item) {
                $this->addItem->handle($quote, $item, "items.{$i}.fields");
            }

            return $quote->refresh();
        });
    }
}
