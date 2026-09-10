<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->bigInteger('zona_id')->nullable()->after('is_active');
            $table->foreign('zona_id')->references('id')->on('zonas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['zona_id']);
            $table->dropColumn(['is_active', 'zona_id']);
        });
    }
};
