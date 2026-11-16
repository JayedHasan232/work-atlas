<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PLAN M12 (D50): the two-model cross-check result on a value slot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_values', function (Blueprint $table) {
            $table->string('cross_check', 16)->nullable()->index();
            $table->timestampTz('cross_checked_at')->nullable();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE field_values
                ADD CONSTRAINT field_values_cross_check_check CHECK (
                    cross_check IS NULL OR cross_check IN ('agreed', 'disagreed')
                ),
                ADD CONSTRAINT field_values_cross_checked_check CHECK (
                    (cross_check IS NULL) = (cross_checked_at IS NULL)
                )
            SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE field_values DROP CONSTRAINT field_values_cross_checked_check, DROP CONSTRAINT field_values_cross_check_check');

        Schema::table('field_values', function (Blueprint $table) {
            $table->dropIndex(['cross_check']);
            $table->dropColumn(['cross_check', 'cross_checked_at']);
        });
    }
};
