<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The words people see for a field definition, per locale (M7): its label, a help text and a label for
 * each option. They come from `data/fields/*.yaml` through `atlas:fields:sync`. Unlike the options and
 * anchors, they can be reworded without a new version.
 *
 * - `label`, `help`: {"bn": "…", "en": "…"}
 * - `option_labels`: {"<option>": {"bn": "…", "en": "…"}, …}, for enum, multi-enum and band fields
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_definitions', function (Blueprint $table) {
            $table->jsonb('label')->nullable();
            $table->jsonb('help')->nullable();
            $table->jsonb('option_labels')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('field_definitions', function (Blueprint $table) {
            $table->dropColumn(['label', 'help', 'option_labels']);
        });
    }
};
