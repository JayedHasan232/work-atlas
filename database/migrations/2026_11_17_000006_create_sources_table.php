<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where a value comes from. Numbers, legal requirements and statistics need at least one (rule 2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->text('url');
            $table->text('publisher')->nullable();
            $table->text('title')->nullable();
            $table->string('license', 64)->nullable();
            $table->date('accessed_on');
            $table->text('archive_url')->nullable();
            $table->timestampsTz();

            $table->index('url');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE sources
                ADD CONSTRAINT sources_url_check CHECK (url ~ '^https?://'),
                ADD CONSTRAINT sources_archive_url_check CHECK (archive_url IS NULL OR archive_url ~ '^https?://')
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
