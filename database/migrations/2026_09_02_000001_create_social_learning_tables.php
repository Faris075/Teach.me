<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('addressee_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['requester_id', 'addressee_id']);
            $table->index(['status', 'addressee_id']);
        });

        Schema::create('classroom_friend_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('invitee_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
            $table->string('message', 300)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'inviter_id', 'invitee_id'], 'classroom_friend_invites_unique_triplet');
            $table->index(['invitee_id', 'status']);
        });

        Schema::create('ai_recommendation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('prompt');
            $table->json('recommendations')->nullable();
            $table->string('provider', 50)->default('local-fallback');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendation_logs');
        Schema::dropIfExists('classroom_friend_invites');
        Schema::dropIfExists('friendships');
    }
};
