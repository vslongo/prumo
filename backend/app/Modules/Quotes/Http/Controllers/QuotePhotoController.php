<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Controllers;

use App\Modules\Quotes\Actions\ConfirmQuoteItemPhoto;
use App\Modules\Quotes\Actions\ListQuotePhotos;
use App\Modules\Quotes\Actions\RemoveQuoteItemPhoto;
use App\Modules\Quotes\Actions\RequestQuoteItemPhotoUpload;
use App\Modules\Quotes\Http\Requests\ItemPhotoUploadRequest;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Fotos por item. O arquivo vai do navegador direto para o R2; aqui só URL, confirmação e lista. */
final class QuotePhotoController
{
    public function index(Quote $quote, ListQuotePhotos $list): JsonResponse
    {
        Gate::authorize('view', $quote);

        return response()->json(['data' => $list->handle($quote)]);
    }

    public function store(ItemPhotoUploadRequest $request, Quote $quote, QuoteItem $item, RequestQuoteItemPhotoUpload $requestUpload): JsonResponse
    {
        Gate::authorize('update', $quote);

        return response()->json(['data' => $requestUpload->handle($quote, $item, $request->string('mime')->toString(), $request->integer('size'))], 201);
    }

    public function confirm(Quote $quote, QuoteItem $item, string $media, ConfirmQuoteItemPhoto $confirm): Response
    {
        Gate::authorize('update', $quote);
        $confirm->handle($item, $media);

        return response()->noContent();
    }

    public function destroy(Quote $quote, QuoteItem $item, string $media, RemoveQuoteItemPhoto $remove): Response
    {
        Gate::authorize('update', $quote);
        $remove->handle($quote, $item, $media);

        return response()->noContent();
    }
}
