<?php

declare(strict_types=1);

use App\Modules\Media\Actions\ConfirmUpload;
use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('r2');
    actingAsRole();
});

function requestUpload(string $mime = 'image/jpeg', int $size = 1000): Media
{
    return app(RequestUploadUrl::class)->handle('service_order', (string) str()->uuid(), $mime, $size)['media'];
}

it('gera URL pré-assinada sem tenant na chave e confirma upload válido', function () {
    $media = requestUpload();
    Storage::disk('r2')->put($media->r2_key, "\xFF\xD8\xFF".str_repeat('x', 100));

    app(ConfirmUpload::class)->handle($media);

    expect($media->r2_key)->toStartWith('service_order/')->not->toContain($media->tenant_id)
        ->and($media->refresh()->status->value)->toBe('uploaded');
});

it('recusa tipo não permitido e tamanho acima de 2 MB no pedido', function () {
    expect(fn () => requestUpload('image/gif'))->toThrow(ValidationException::class)
        ->and(fn () => requestUpload('image/jpeg', 3 * 1024 * 1024))->toThrow(ValidationException::class);
});

it('marca failed e apaga arquivo maior que 2 MB ou com conteúdo de outro tipo', function (string $content) {
    $media = requestUpload();
    Storage::disk('r2')->put($media->r2_key, $content);

    expect(fn () => app(ConfirmUpload::class)->handle($media))->toThrow(ValidationException::class);
    expect($media->refresh()->status->value)->toBe('failed')
        ->and(Storage::disk('r2')->exists($media->r2_key))->toBeFalse();
})->with([
    'grande demais' => fn () => "\xFF\xD8\xFF".str_repeat('x', 2 * 1024 * 1024),
    'não é JPEG' => fn () => '%PDF-1.4 '.str_repeat('x', 100),
]);

it('isola mídia entre tenants', function () {
    $foreign = inOtherTenant(fn () => requestUpload());

    expect(Media::query()->find($foreign->id))->toBeNull();
});
