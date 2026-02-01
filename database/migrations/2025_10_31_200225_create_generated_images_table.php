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
        Schema::create('generated_images', function (Blueprint $table) {
            $table->id();
    $table->foreignId('conversation_id')
          ->constrained('chatbot_conversations')
          ->onDelete('cascade'); // علشان لو اتحذفت المحادثة تتحذف الصور كمان
    $table->foreignId('place_id')->nullable()->constrained()->onDelete('cascade');
    $table->string('image_url'); // الصورة المولدة
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_images');
    }
};
