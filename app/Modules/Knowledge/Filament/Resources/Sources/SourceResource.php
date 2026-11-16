<?php

namespace App\Modules\Knowledge\Filament\Resources\Sources;

use App\Modules\Knowledge\Filament\Resources\Sources\Pages\ListSources;
use App\Modules\Knowledge\Filament\Resources\Sources\Pages\ViewSource;
use App\Modules\Knowledge\Models\Source;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Admin view of sources (M8): list and show only.
 */
final class SourceResource extends ReadOnlyResource
{
    protected static ?string $model = Source::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    public static function getModelLabel(): string
    {
        return __('admin.sources.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.sources.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')->label(__('admin.fields.title'))->placeholder('—'),
            TextEntry::make('url')->label(__('admin.fields.url'))->url(fn (Source $record): string => $record->url)->openUrlInNewTab(),
            TextEntry::make('publisher')->label(__('admin.fields.publisher'))->placeholder('—'),
            TextEntry::make('license')->label(__('admin.fields.license'))->placeholder('—'),
            TextEntry::make('accessed_on')->label(__('admin.fields.accessed_on'))->date(),
            TextEntry::make('archive_url')->label(__('admin.fields.archive_url'))->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('title')->label(__('admin.fields.title'))->searchable()->limit(60)->placeholder('—'),
                TextColumn::make('publisher')->label(__('admin.fields.publisher'))->searchable()->placeholder('—'),
                TextColumn::make('license')->label(__('admin.fields.license'))->badge()->placeholder('—'),
                TextColumn::make('accessed_on')->label(__('admin.fields.accessed_on'))->date()->sortable(),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSources::route('/'),
            'view' => ViewSource::route('/{record}'),
        ];
    }
}
