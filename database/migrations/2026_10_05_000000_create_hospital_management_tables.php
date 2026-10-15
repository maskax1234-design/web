<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments',
            function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            }
        );
        Schema::create('patients',
            function (Blueprint $table): void {
                $table->id();
                $table->string('patient_number')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('gender',
                    20);
                $table->date('date_of_birth');
                $table->string('phone',
                    32);
                $table->string('email')->nullable()->index();
                $table->text('address')->nullable();
                $table->string('emergency_contact',
                    100)->nullable();
                $table->string('blood_group',
                    10)->nullable();
                $table->text('allergies')->nullable();
                $table->longText('medical_history')->nullable();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            }
        );
        Schema::create('doctors',
            function (Blueprint $table): void {
                $table->id();
                $table->string('doctor_number')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('gender',
                    20);
                $table->string('phone',
                    32);
                $table->string('email')->unique();
                $table->string('specialization')->index();
                $table->foreignId('department_id')->constrained()->restrictOnDelete();
                $table->string('qualification');
                $table->string('license_number')->unique();
                $table->unsignedSmallInteger('experience')->default(0);
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            }
        );
        Schema::create('appointments',
            function (Blueprint $table): void {
                $table->id();
                $table->string('appointment_number')->unique();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->restrictOnDelete();
                $table->date('appointment_date')->index();
                $table->time('appointment_time');
                $table->text('reason');
                $table->string('status')->default('Scheduled')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique([
                    'doctor_id',
                    'appointment_date',
                    'appointment_time',
                ]
                );
            }
        );
        Schema::create('medicines',
            function (Blueprint $table): void {
                $table->id();
                $table->string('medicine_code')->unique();
                $table->string('name')->index();
                $table->string('generic_name')->nullable();
                $table->string('category')->nullable()->index();
                $table->string('manufacturer')->nullable();
                $table->string('dosage_form');
                $table->string('strength')->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->decimal('unit_price',
                    12,
                    2);
                $table->date('expiry_date')->index();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            }
        );
        Schema::create('prescriptions',
            function (Blueprint $table): void {
                $table->id();
                $table->string('prescription_number')->unique();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->date('prescription_date')->index();
                $table->text('instructions')->nullable();
                $table->string('status')->default('Active')->index();
                $table->timestamps();
            }
        );
        Schema::create('medicine_prescription',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
                $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
                $table->string('dosage');
                $table->string('frequency');
                $table->string('duration');
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->text('instructions')->nullable();
                $table->timestamps();
                $table->unique([
                    'prescription_id',
                    'medicine_id',
                ]
                );
            }
        );
        Schema::create('prescription_items',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
                $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
                $table->string('dosage');
                $table->string('frequency');
                $table->string('duration');
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->text('instructions')->nullable();
                $table->timestamps();
            }
        );
        Schema::create('invoices',
            function (Blueprint $table): void {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
                $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
                $table->date('invoice_date')->index();
                $table->decimal('subtotal',
                    12,
                    2)->default(0);
                $table->decimal('discount',
                    12,
                    2)->default(0);
                $table->decimal('tax',
                    12,
                    2)->default(0);
                $table->decimal('tax_percent',
                    12,
                    2)->default(0);
                $table->decimal('tax_amount',
                    12,
                    2)->default(0);
                $table->decimal('total',
                    12,
                    2)->default(0);
                $table->decimal('grand_total',
                    12,
                    2)->default(0);
                $table->decimal('paid_amount',
                    12,
                    2)->default(0);
                $table->decimal('due_amount',
                    12,
                    2)->default(0);
                $table->string('status')->default('UNPAID')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            }
        );
        Schema::create('invoice_items',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('medicine_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->timestamps();
                $table->unique(['invoice_id', 'medicine_id']);
            }
        );
        Schema::create('payments',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
                $table->string('payment_number')->unique();
                $table->decimal('amount',
                    12,
                    2);
                $table->date('payment_date')->index();
                $table->string('payment_method');
                $table->string('reference')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('medicine_prescription');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicines');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('departments');
    }
};
