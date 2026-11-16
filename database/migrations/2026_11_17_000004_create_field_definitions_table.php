<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What can be said about an occupation. A definition is versioned: changing its meaning, options or
 * anchors is a new version, so values stay tied to the scale they were written against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->unsignedSmallInteger('version');
            $table->unsignedSmallInteger('wave');
            $table->string('group', 64);
            $table->string('type', 16);
            $table->jsonb('options')->nullable();
            $table->jsonb('anchors')->nullable();
            $table->boolean('sensitive')->default(false);
            $table->boolean('requires_source')->default(false);
            $table->timestampsTz();

            $table->unique(['key', 'version']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE field_definitions
                ADD CONSTRAINT field_definitions_key_check CHECK (key ~ '^[a-z][a-z0-9_]*$'),
                ADD CONSTRAINT field_definitions_version_check CHECK (version >= 1),
                ADD CONSTRAINT field_definitions_wave_check CHECK (wave BETWEEN 1 AND 6),
                ADD CONSTRAINT field_definitions_type_check
                    CHECK (type IN ('scale_1_5', 'enum', 'multi_enum', 'boolean', 'band', 'text')),
                ADD CONSTRAINT field_definitions_options_check CHECK (
                    (type IN ('enum', 'multi_enum', 'band')) = (options IS NOT NULL)
                ),
                ADD CONSTRAINT field_definitions_anchors_check CHECK (
                    type <> 'scale_1_5' OR (anchors -> 'bn' IS NOT NULL AND anchors -> 'en' IS NOT NULL)
                )
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('field_definitions');
    }
};
