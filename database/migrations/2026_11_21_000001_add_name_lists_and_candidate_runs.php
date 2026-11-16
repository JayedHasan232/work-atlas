<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M10 (PLAN D44, D45).
 *
 * - Field definitions may be `name_list` (an occupation's names in one language) and wave 0, the
 *   taxonomy wave that holds the `names` field.
 * - An occupation proposed by an AI run keeps that run's id (`occupations.ai_run_id`), so the list
 *   review (M11) can show why it was proposed. A run with occupations can't be deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE field_definitions
                DROP CONSTRAINT field_definitions_wave_check,
                DROP CONSTRAINT field_definitions_type_check,
                ADD CONSTRAINT field_definitions_wave_check CHECK (wave BETWEEN 0 AND 6),
                ADD CONSTRAINT field_definitions_type_check
                    CHECK (type IN ('scale_1_5', 'enum', 'multi_enum', 'boolean', 'band', 'text', 'name_list'))
            SQL);

        Schema::table('occupations', function (Blueprint $table) {
            $table->foreignId('ai_run_id')->nullable()->after('merged_into_id')->constrained('ai_runs')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('occupations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_run_id');
        });

        DB::statement(<<<'SQL'
            ALTER TABLE field_definitions
                DROP CONSTRAINT field_definitions_wave_check,
                DROP CONSTRAINT field_definitions_type_check,
                ADD CONSTRAINT field_definitions_wave_check CHECK (wave BETWEEN 1 AND 6),
                ADD CONSTRAINT field_definitions_type_check
                    CHECK (type IN ('scale_1_5', 'enum', 'multi_enum', 'boolean', 'band', 'text'))
            SQL);
    }
};
