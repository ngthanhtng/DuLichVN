<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable(); // ID người dùng (nếu có)
            $table->text('message'); // Tin nhắn từ người dùng
            $table->text('response'); // Phản hồi từ chatbot
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat_histories');
    }
};
