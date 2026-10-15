<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 100);
            $table->unsignedInteger('quantity_received');
            $table->unsignedInteger('quantity_available');
            $table->date('expiry_date')->nullable()->index();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['medicine_id', 'batch_number']);
            $table->index(['medicine_id', 'quantity_available']);
        });

        Schema::create('pharmacy_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
            $table->foreignId('batch_id')->constrained('pharmacy_stock_batches')->restrictOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('movement_type', 32)->index();
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'movement_type']);
        });

        Schema::create('pharmacy_refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('reason');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refunded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_refunds');
        Schema::dropIfExists('pharmacy_stock_movements');
        Schema::dropIfExists('pharmacy_stock_batches');
    }
};
