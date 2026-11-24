<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dados do prestador para a NFS-e Nacional (ADR 0010). Um por oficina.
        Schema::create('fiscal_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained();
            $table->string('cnpj', 14);
            $table->string('municipal_registration', 20)->nullable();
            $table->string('city_ibge_code', 7);
            $table->string('tax_regime', 20);
            $table->string('national_tax_code', 6);
            $table->string('municipal_tax_code', 20)->nullable();
            // Alíquota do ISS em pontos-base (2,5% = 250). MEI não informa.
            $table->unsignedSmallInteger('iss_rate_bp')->nullable();
            $table->string('dps_series', 5)->default('1');
            $table->boolean('include_products')->default(true);
            $table->string('environment', 20)->default('restricted');
            // .pfx no R2 (via Media) + senha criptografada. Nunca sai em log ou resposta.
            $table->foreignUuid('certificate_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->text('certificate_password')->nullable();
            // Hash dos bytes validados: quem for usar confere que o objeto no R2 não foi trocado.
            $table->string('certificate_sha256', 64)->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_profiles');
    }
};
