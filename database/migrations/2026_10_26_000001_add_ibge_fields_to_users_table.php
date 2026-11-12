<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'current_city_ibge_code')) {
                $table->unsignedInteger('current_city_ibge_code')->nullable()->after('current_city_name');
            }
            if (!Schema::hasColumn('users', 'current_city_state')) {
                $table->string('current_city_state', 2)->nullable()->after('current_city_ibge_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['current_city_state', 'current_city_ibge_code'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
