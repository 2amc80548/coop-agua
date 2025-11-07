<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->string("queue", 255);
            $table->longText("payload");
            $table->smallInteger("attempts") /* was UNSIGNED */;
            $table->integer("reserved_at")->nullable() /* was UNSIGNED */;
            $table->integer("available_at") /* was UNSIGNED */;
            $table->integer("created_at") /* was UNSIGNED */;
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
