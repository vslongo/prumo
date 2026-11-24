<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Oficina que já emitiu pelo Emissor Nacional/outro sistema na mesma série começa depois do último número usado.
        Schema::table('fiscal_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('dps_next_number')->nullable()->after('dps_series');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_profiles', function (Blueprint $table) {
            $table->dropColumn('dps_next_number');
        });
    }
};
