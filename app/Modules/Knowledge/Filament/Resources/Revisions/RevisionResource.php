<?php

namespace App\Modules\Knowledge\Filament\Resources\Revisions;

use App\Modules\Knowledge\Filament\Resources\Revisions\Pages\ListRevisions;
use App\Modules\Knowledge\Filament\Resources\Revisions\Pages\ViewRevision;
use App\Modules\Knowledge\Models\Revision;
use App\Modules\Knowledge\Queries\RevisionDiff;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Admin view of revisions (M8): list and show only.
 */
final class RevisionResource extends ReadOnlyResource
{
    protected static ?string $model = Revision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function getModelLabel(): string
    {
        return __('admin.revisions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.revisions.plural');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('fieldValue.definition.key')->label(__('admin.fields.field')),
            TextEntry::make('fieldValue.occupation_id')->label(__('admin.fields.occupation_id')),
            TextEntry::make('author')->label(__('admin.fields.author'))->state(fn (Revision $record): string => self::author($record)),
            IconEntry::make('proposed')->label(__('admin.fields.proposed'))->boolean(),
            TextEntry::make('confidence')->label(__('admin.fields.confidence'))->placeholder('—'),
            TextEntry::make('old_status')->label(__('admin.fields.old_status'))->placeholder('—'),
            TextEntry::make('reverts_revision_id')->label(__('admin.fields.reverts_revision_id'))->placeholder('—'),
            TextEntry::make('comment')->label(__('admin.fields.comment'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('sources.url')->label(__('admin.fields.sources'))->listWithLineBreaks()->placeholder('—')->columnSpanFull(),
            TextEntry::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka'),
            ViewEntry::make('diff')->label(__('admin.fields.diff'))
                ->state(fn (Revision $record): array => app(RevisionDiff::class)->handle($record->id))
                ->view('admin.revision-diff')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('fieldValue.definition.key')->label(__('admin.fields.field')),
                TextColumn::make('fieldValue.occupation_id')->label(__('admin.fields.occupation_id')),
                TextColumn::make('author')->label(__('admin.fields.author'))->state(fn (Revision $record): string => self::author($record)),
                IconColumn::make('proposed')->label(__('admin.fields.proposed'))->boolean(),
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('proposed')->label(__('admin.fields.proposed')),
            ])
            ->recordActions([ViewAction::make()]);
    }

    /**
     * Who wrote the revision: a person or an AI run, by id (cross-module rows are linked by id, D20).
     */
    private static function author(Revision $record): string
    {
        return $record->user_id !== null
            ? __('admin.revisions.author_user', ['id' => $record->user_id])
            : __('admin.revisions.author_ai_run', ['id' => (string) $record->ai_run_id]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRevisions::route('/'),
            'view' => ViewRevision::route('/{record}'),
        ];
    }
}
