<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'birth_city_id')) {
                $table->foreignId('birth_city_id')
                    ->nullable()
                    ->constrained('cities')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'current_city_id')) {
                $table->foreignId('current_city_id')
                    ->nullable()
                    ->constrained('cities')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false);
            }

            if (!Schema::hasColumn('users', 'marketing_consent_at')) {
                $table->timestamp('marketing_consent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            foreach (['birth_city_id', 'current_city_id', 'marketing_consent', 'marketing_consent_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
