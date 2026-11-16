<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sources attached to a revision. Part of the revision's record, so it is append-only too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_source', function (Blueprint $table) {
            $table->foreignId('revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(['revision_id', 'source_id']);
            $table->index('source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_source');
    }
};
