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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'failed'])->default('pending');
            $table->string('receipt_image')->nullable()->comment('مسار صورة إيصال الدفع');
            $table->string('transaction_id')->nullable()->comment('رقم العملية من المحفظة أو البنك');
            $table->string('payment_method')->nullable()->comment('طريقة الدفع: فودافون كاش، إنستا باي، إلخ');
            $table->text('notes')->nullable();
            $table->morphs('payable'); // payable_type & payable_id
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
