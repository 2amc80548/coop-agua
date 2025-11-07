<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->bigInteger("role_id") /* was UNSIGNED */;
            $table->string("model_type", 255);
            $table->bigInteger("model_id") /* was UNSIGNED */;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
    }
};
