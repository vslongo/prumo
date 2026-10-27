<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->string('name');
            $table->string('email')->nullable();
            $table->text('phone')->nullable();
            $table->string('phone_hash', 64)->nullable();
            $table->text('document')->nullable();
            $table->string('document_hash', 64)->nullable();
            $table->timestamp('consent_whatsapp_at')->nullable();
            $table->string('consent_whatsapp_source', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'phone_hash']);
            $table->index(['tenant_id', 'document_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
