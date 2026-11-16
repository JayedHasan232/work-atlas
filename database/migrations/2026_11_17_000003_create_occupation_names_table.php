<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every name an occupation is known by: one primary name per locale, plus aliases, local and regional
 * names (বিদ্যুৎ মিস্ত্রি, নলমিস্ত্রি) so search finds them. `normalized_name` is the search key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occupation_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occupation_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->text('name');
            $table->text('normalized_name');
            $table->string('type', 16);
            $table->string('region', 32)->nullable();
            $table->timestampsTz();

            $table->unique(['occupation_id', 'locale', 'normalized_name']);
            $table->index(['locale', 'normalized_name']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE occupation_names
                ADD CONSTRAINT occupation_names_type_check
                    CHECK (type IN ('primary', 'alias', 'local', 'regional')),
                ADD CONSTRAINT occupation_names_region_check
                    CHECK ((type = 'regional') = (region IS NOT NULL)),
                ADD CONSTRAINT occupation_names_not_blank_check
                    CHECK (btrim(name) <> '' AND btrim(normalized_name) <> '')
            SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX occupation_names_one_primary_per_locale
                ON occupation_names (occupation_id, locale) WHERE type = 'primary'
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('occupation_names');
    }
};
