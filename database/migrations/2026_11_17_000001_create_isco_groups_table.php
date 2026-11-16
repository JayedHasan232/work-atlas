<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ISCO-08 hierarchy (D4): major (1 digit), sub-major (2), minor (3) and unit groups (4). Each code
 * starts with its parent's code. BSCO-2012 codes and Bangla titles arrive later (M6b), so both are
 * nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('isco_groups', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->unsignedSmallInteger('level');
            $table->string('parent_code', 4)->nullable()->index();
            $table->text('title_en');
            $table->text('title_bn')->nullable();
            $table->string('bsco_code', 8)->nullable()->index();
            $table->timestampsTz();
        });

        // A separate step: the self-reference needs the unique index on `code` to exist first.
        Schema::table('isco_groups', function (Blueprint $table) {
            $table->foreign('parent_code')->references('code')->on('isco_groups')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE isco_groups
                ADD CONSTRAINT isco_groups_level_check CHECK (level BETWEEN 1 AND 4),
                ADD CONSTRAINT isco_groups_code_check CHECK (code ~ '^[0-9]+$' AND length(code) = level),
                ADD CONSTRAINT isco_groups_parent_check CHECK (
                    (level = 1 AND parent_code IS NULL)
                    OR (level > 1 AND parent_code = left(code, level - 1))
                )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('isco_groups');
    }
};
