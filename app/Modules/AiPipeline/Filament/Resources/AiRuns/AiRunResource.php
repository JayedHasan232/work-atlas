<?php

namespace App\Modules\AiPipeline\Filament\Resources\AiRuns;

use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Filament\Resources\AiRuns\Pages\ListAiRuns;
use App\Modules\AiPipeline\Filament\Resources\AiRuns\Pages\ViewAiRun;
use App\Modules\AiPipeline\Models\AiRun;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Admin view of ai runs (M8): list and show only.
 */
final class AiRunResource extends ReadOnlyResource
{
    protected static ?string $model = AiRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function getModelLabel(): string
    {
        return __('admin.ai_runs.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.ai_runs.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('status')->label(__('admin.fields.status'))->badge(),
            TextEntry::make('provider')->label(__('admin.fields.provider')),
            TextEntry::make('model')->label(__('admin.fields.model')),
            TextEntry::make('prompt_key')->label(__('admin.fields.prompt_key')),
            TextEntry::make('prompt_version')->label(__('admin.fields.prompt_version')),
            TextEntry::make('input_hash')->label(__('admin.fields.input_hash')),
            TextEntry::make('batch_id')->label(__('admin.fields.batch_id'))->placeholder('—'),
            TextEntry::make('input_tokens')->label(__('admin.fields.input_tokens'))->placeholder('—'),
            TextEntry::make('output_tokens')->label(__('admin.fields.output_tokens'))->placeholder('—'),
            TextEntry::make('cost_usd')->label(__('admin.fields.cost_usd'))->placeholder('—'),
            TextEntry::make('started_at')->label(__('admin.fields.started_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('finished_at')->label(__('admin.fields.finished_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('error')->label(__('admin.fields.error'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('raw_output')->label(__('admin.fields.raw_output'))->placeholder('—')->fontFamily('mono')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge(),
                TextColumn::make('provider')->label(__('admin.fields.provider')),
                TextColumn::make('model')->label(__('admin.fields.model'))->searchable(),
                TextColumn::make('prompt_key')->label(__('admin.fields.prompt_key'))->searchable(),
                TextColumn::make('prompt_version')->label(__('admin.fields.prompt_version')),
                TextColumn::make('cost_usd')->label(__('admin.fields.cost_usd'))->placeholder('—'),
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.fields.status'))->options(AiRunStatus::class),
                SelectFilter::make('model')->label(__('admin.fields.model'))
                    ->options(fn (): array => AiRun::query()->distinct()->orderBy('model')->pluck('model', 'model')->all()),
                SelectFilter::make('prompt_version')->label(__('admin.fields.prompt_version'))
                    ->options(fn (): array => AiRun::query()->distinct()->orderBy('prompt_version')->pluck('prompt_version', 'prompt_version')->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiRuns::route('/'),
            'view' => ViewAiRun::route('/{record}'),
        ];
    }
}
