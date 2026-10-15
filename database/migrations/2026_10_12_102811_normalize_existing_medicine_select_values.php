<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categoryOptions = [
            'Analgesic',
            'Antibiotic',
            'Antihistamine',
            'Cardiovascular',
            'Dermatology',
            'Endocrine',
            'Ophthalmic',
            'Psychiatric',
            'Respiratory',
            'Vitamin',
        ];
        $dosageFormOptions = [
            'Tablet',
            'Capsule',
            'Syrup',
            'Suspension',
            'Injection',
            'Ointment',
            'Cream',
            'Gel',
            'Drops',
            'Inhaler',
            'Suppository',
        ];

        foreach ($categoryOptions as $index => $category) {
            DB::table('medicines')
                ->where('category', (string) $index)
                ->update(['category' => $category]);
        }

        foreach ($dosageFormOptions as $index => $dosageForm) {
            DB::table('medicines')
                ->where('dosage_form', (string) $index)
                ->update(['dosage_form' => $dosageForm]);
        }

        foreach (['Active', 'Inactive', 'Expired'] as $index => $status) {
            DB::table('medicines')
                ->where('status', (string) $index)
                ->update(['status' => $status]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The normalized labels are canonical medicine data; reverting them would restore invalid select values.
    }
};
