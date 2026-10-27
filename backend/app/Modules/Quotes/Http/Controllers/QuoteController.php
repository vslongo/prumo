<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Controllers;

use App\Modules\Quotes\Actions\AddQuoteItem;
use App\Modules\Quotes\Actions\CreateQuote;
use App\Modules\Quotes\Actions\ListQuotes;
use App\Modules\Quotes\Actions\RemoveQuoteItem;
use App\Modules\Quotes\Actions\SendQuote;
use App\Modules\Quotes\Data\CreateQuoteData;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Http\Requests\ListQuotesRequest;
use App\Modules\Quotes\Http\Requests\StoreQuoteItemRequest;
use App\Modules\Quotes\Http\Requests\StoreQuoteRequest;
use App\Modules\Quotes\Http\Resources\QuoteResource;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class QuoteController
{
    public function index(ListQuotesRequest $request, ListQuotes $list): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Quote::class);

        return QuoteResource::collection($list->handle(
            $request->enum('status', QuoteStatus::class),
            $request->validated('vehicle_id'),
        ));
    }

    public function store(StoreQuoteRequest $request, CreateQuote $create): QuoteResource
    {
        Gate::authorize('create', Quote::class);

        $quote = $create->handle(CreateQuoteData::fromArray($request->validated(), (string) $request->user()?->id));

        return new QuoteResource($quote->load(['items', 'customer', 'vehicle']));
    }

    public function show(Quote $quote): QuoteResource
    {
        Gate::authorize('view', $quote);

        return new QuoteResource($quote->load(['items', 'approval', 'customer', 'vehicle']));
    }

    public function addItem(StoreQuoteItemRequest $request, Quote $quote, AddQuoteItem $add): QuoteResource
    {
        Gate::authorize('update', $quote);
        $add->handle($quote, QuoteItemData::fromArray($request->validated()));

        return new QuoteResource($quote->refresh()->load(['items', 'customer', 'vehicle']));
    }

    public function removeItem(Quote $quote, QuoteItem $item, RemoveQuoteItem $remove): QuoteResource
    {
        Gate::authorize('update', $quote);
        $remove->handle($quote, $item);

        return new QuoteResource($quote->refresh()->load(['items', 'customer', 'vehicle']));
    }

    public function send(Quote $quote, SendQuote $send): JsonResponse
    {
        Gate::authorize('update', $quote);
        $sent = $send->handle($quote);

        return response()->json([
            'data' => new QuoteResource($sent['quote']->load(['items', 'customer', 'vehicle'])),
            'link' => $sent['link'],
            'whatsapp_url' => $sent['whatsapp_url'],
            'expires_at' => $sent['expires_at'],
        ]);
    }
}
