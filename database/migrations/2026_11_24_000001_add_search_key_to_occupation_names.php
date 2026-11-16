<?php

use App\Modules\Taxonomy\Actions\NormalizeForSearch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Search (PLAN M15, D58): `occupation_names.search_key` is the loose key that search matches, with
 * a full-text index (`simple` config, for word prefixes) and a trigram index (for misspellings).
 * Existing rows are filled in here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occupation_names', function (Blueprint $table) {
            $table->text('search_key')->nullable();
        });

        $normalize = app(NormalizeForSearch::class);

        DB::table('occupation_names')->select(['id', 'name'])->orderBy('id')->chunkById(500, function ($rows) use ($normalize): void {
            foreach ($rows as $row) {
                DB::table('occupation_names')->where('id', $row->id)->update(['search_key' => $normalize->handle($row->name)]);
            }
        });

        DB::statement('ALTER TABLE occupation_names ALTER COLUMN search_key SET NOT NULL');
        DB::statement('CREATE INDEX occupation_names_search_key_trgm_index ON occupation_names USING gin (search_key gin_trgm_ops)');
        DB::statement("CREATE INDEX occupation_names_search_key_fts_index ON occupation_names USING gin (to_tsvector('simple', search_key))");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS occupation_names_search_key_fts_index');
        DB::statement('DROP INDEX IF EXISTS occupation_names_search_key_trgm_index');

        Schema::table('occupation_names', function (Blueprint $table) {
            $table->dropColumn('search_key');
        });
    }
};
