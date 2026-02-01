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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('activity_type', [
                'search', 'visit', 'like', 'comment', 'plan_creation'
            ]);
            $table->string('search_query')->nullable(); // لو نوع النشاط search
            $table->foreignId('place_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('details')->nullable(); // أي تفاصيل إضافية
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
