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
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('source', 32)->default('CLINICAL')->index();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('pos_token')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['pos_token']);
            $table->dropConstrainedForeignId('cashier_id');
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'pos_token']);
        });
    }
};
