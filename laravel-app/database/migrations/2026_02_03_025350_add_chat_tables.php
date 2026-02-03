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
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('private');    // private | group
            $table->string('title')->nullable(); // group name, null nếu chat 1-1
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        
            $table->foreign('created_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
        
        Schema::create('chat_conversation_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_conversation_id');
            $table->unsignedBigInteger('user_id');
        
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_read_at')->nullable(); // Count unread messages
        
            $table->timestamps();
        
            $table->unique(['chat_conversation_id', 'user_id']);
        
            $table->foreign('chat_conversation_id')
                ->references('id')->on('chat_conversations')
                ->cascadeOnDelete();
        
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_conversation_id');
            $table->unsignedBigInteger('user_id');
        
            $table->text('content')->nullable();
            $table->string('type')->default('text'); // text | image | file | system
            
            $table->json('meta')->nullable(); // link, file info, mention, etc
            
            $table->timestamps();
        
            $table->foreign('chat_conversation_id')
                ->references('id')->on('chat_conversations')
                ->cascadeOnDelete();
        
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        
            $table->index(['chat_conversation_id', 'created_at']);
        });

        Schema::create('chat_message_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
        
            $table->unique(['chat_message_id', 'user_id']);
        
            $table->foreign('chat_message_id')
                ->references('id')->on('chat_messages')
                ->cascadeOnDelete();
        
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
