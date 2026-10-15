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
            if (! Schema::hasColumn('invoices', 'prescription_id')) {
                $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('invoices', 'tax_percent')) {
                $table->decimal('tax_percent', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 12, 2)->default(0);
            }

            if (! Schema::hasColumn('invoices', 'grand_total')) {
                $table->decimal('grand_total', 12, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (Schema::hasColumn('invoices', 'prescription_id')) {
                $table->dropConstrainedForeignId('prescription_id');
            }

            $columnsToDrop = array_filter(
                ['tax_percent', 'tax_amount', 'grand_total'],
                fn (string $column): bool => Schema::hasColumn('invoices', $column),
            );

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
