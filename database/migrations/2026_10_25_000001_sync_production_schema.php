<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addUserProfileColumns();
        $this->addPlaceMediaColumns();
        $this->createBusinessClaimsTable();
        $this->normalizePlaceStatuses();
    }

    public function down(): void
    {
        if (Schema::hasTable('business_claims')) {
            Schema::drop('business_claims');
        }

        Schema::table('places', function (Blueprint $table): void {
            foreach (['cover_image', 'images'] as $column) {
                if (Schema::hasColumn('places', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'birth_city_name',
                'current_city_name',
                'cariri_city_id',
                'city_preference',
                'marketing_consent_at',
                'marketing_consent',
                'current_city_id',
                'birth_city_id',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addUserProfileColumns(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'birth_city_id')) {
                $table->foreignId('birth_city_id')->nullable()->constrained('cities')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'birth_city_name')) {
                $table->string('birth_city_name', 120)->nullable();
            }

            if (!Schema::hasColumn('users', 'current_city_id')) {
                $table->foreignId('current_city_id')->nullable()->constrained('cities')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'current_city_name')) {
                $table->string('current_city_name', 120)->nullable();
            }

            if (!Schema::hasColumn('users', 'cariri_city_id')) {
                $table->foreignId('cariri_city_id')->nullable()->constrained('cities')->nullOnDelete();
            }

            if (!Schema::hasColumn('users', 'city_preference')) {
                $table->string('city_preference', 120)->nullable();
            }

            if (!Schema::hasColumn('users', 'marketing_consent')) {
                $table->boolean('marketing_consent')->default(false);
            }

            if (!Schema::hasColumn('users', 'marketing_consent_at')) {
                $table->timestamp('marketing_consent_at')->nullable();
            }
        });
    }

    private function addPlaceMediaColumns(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            if (!Schema::hasColumn('places', 'cover_image')) {
                $table->string('cover_image', 2048)->nullable();
            }

            if (!Schema::hasColumn('places', 'images')) {
                $table->json('images')->nullable();
            }
        });
    }

    private function createBusinessClaimsTable(): void
    {
        if (Schema::hasTable('business_claims')) {
            return;
        }

        Schema::create('business_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->string('proof', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reject_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['place_id', 'user_id']);
        });
    }

    private function normalizePlaceStatuses(): void
    {
        if (!Schema::hasColumn('places', 'status')) {
            return;
        }

        DB::table('places')->where('status', 'active')->update(['status' => 'published']);
        DB::table('places')->where('status', 'inactive')->update(['status' => 'archived']);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE `places` MODIFY `status` ENUM('draft','pending','published','rejected','archived') NOT NULL DEFAULT 'published'"
            );
        }
    }
};
