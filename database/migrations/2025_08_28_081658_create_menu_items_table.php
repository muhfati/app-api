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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('menu_group_id')->constrained('menu_groups')->onDelete('cascade');
            $table->string('name')->unique();
            $table->string('sw_name')->unique();
            $table->string('url')->nullable();
            $table->string('icon');
            $table->integer('sort_order')->default('1');
            $table->uuid('uuid');
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
