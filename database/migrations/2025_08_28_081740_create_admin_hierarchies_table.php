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
        Schema::create('admin_hierarchies', function (Blueprint $table) {
            $table->id();
            
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('iso_code')->unique();
            $table->text('label');
            $table->integer('parent_id')->nullable();
            $table->foreignId('admin_hierarchy_level_id')->constrained('admin_hierarchy_levels')->onDelete('cascade');
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
        Schema::dropIfExists('admin_hierarchies');
    }
};
