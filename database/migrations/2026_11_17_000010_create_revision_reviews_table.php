<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Review decisions on revisions (D1). A new decision is a new row; earlier ones are never changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('decision', 16);
            $table->text('comment')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['revision_id', 'id']);
            $table->index('reviewer_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE revision_reviews
                ADD CONSTRAINT revision_reviews_decision_check CHECK (decision IN ('accepted', 'rejected'))
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_reviews');
    }
};
