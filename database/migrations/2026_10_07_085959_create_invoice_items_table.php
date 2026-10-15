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
        if (! Schema::hasTable('invoice_items')) {
            Schema::create('invoice_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['invoice_id', 'medicine_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
