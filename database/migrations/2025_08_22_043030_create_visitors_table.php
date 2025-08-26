<?php

// database/migrations/xxxx_xx_xx_create_visitors_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->date('visit_date');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('visitors');
    }
};
