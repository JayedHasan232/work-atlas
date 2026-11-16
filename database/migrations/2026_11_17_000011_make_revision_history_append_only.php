<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The database half of D2: UPDATE, DELETE and TRUNCATE on the revision history fail, whoever runs
 * them. The model guard is the other half. `migrate:rollback` still works because it drops the
 * tables (DROP TABLE is not blocked). `OR REPLACE`, because `migrate:fresh` drops tables but leaves
 * functions behind.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = ['revisions', 'revision_reviews', 'revision_source'];

    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION atlas_reject_history_change() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION '% is append-only: % is not allowed', TG_TABLE_NAME, TG_OP
                    USING ERRCODE = 'restrict_violation',
                          HINT = 'Record a new revision instead (a revert is a new revision).';
            END;
            $$
            SQL);

        foreach ($this->tables as $table) {
            DB::statement("CREATE TRIGGER {$table}_append_only BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION atlas_reject_history_change()");
            DB::statement("CREATE TRIGGER {$table}_no_truncate BEFORE TRUNCATE ON {$table} FOR EACH STATEMENT EXECUTE FUNCTION atlas_reject_history_change()");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_no_truncate ON {$table}");
            DB::statement("DROP TRIGGER IF EXISTS {$table}_append_only ON {$table}");
        }

        DB::statement('DROP FUNCTION IF EXISTS atlas_reject_history_change()');
    }
};
