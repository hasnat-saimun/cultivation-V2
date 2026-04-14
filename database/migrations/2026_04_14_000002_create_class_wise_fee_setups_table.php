<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_wise_fee_setups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('fees_type_id');
            $table->decimal('setup_amount', 10, 2);
            $table->timestamps();

            $table->unique(['class_id', 'fees_type_id']);
            $table->index('class_id');
            $table->index('fees_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_wise_fee_setups');
    }
};
