<?php

use App\Enums\VehicleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('role');
            $table->boolean('is_available')->default(false)->after('vehicle_type');
            $table->string('expo_push_token')->nullable()->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vehicle_type', 'is_available', 'expo_push_token']);
        });
    }
};
