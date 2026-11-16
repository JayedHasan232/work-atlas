<?php

namespace App\Modules\Taxonomy\Filament\Resources\Occupations;

use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Filament\ListReview;
use App\Modules\Taxonomy\Filament\Resources\Occupations\Pages\ListOccupations;
use App\Modules\Taxonomy\Filament\Resources\Occupations\Pages\ViewOccupation;
use App\Modules\Taxonomy\Models\Occupation;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Admin view of occupations (M8), with the list-review actions (M11). The actions write through
 * Taxonomy Actions only; the resource itself never creates, edits or deletes a record.
 */
final class OccupationResource extends ReadOnlyResource
{
    protected static ?string $model = Occupation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function getModelLabel(): string
    {
        return __('admin.occupations.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.occupations.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Occupation::query()->where('publish_state', PublishState::Candidate)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return __('admin.review.candidates_badge');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('slug')->label(__('admin.fields.slug')),
            TextEntry::make('isco_code')->label(__('admin.fields.isco_code')),
            TextEntry::make('bsco_code')->label(__('admin.fields.bsco_code'))->placeholder('—'),
            TextEntry::make('publish_state')->label(__('admin.fields.publish_state'))->badge(),
            IconEntry::make('indexable')->label(__('admin.fields.indexable'))->boolean(),
            TextEntry::make('merged_into_id')->label(__('admin.fields.merged_into_id'))->placeholder('—'),
            TextEntry::make('reviewed_at')->label(__('admin.fields.reviewed_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('review_note')->label(__('admin.fields.review_note'))->placeholder('—'),
            RepeatableEntry::make('names')->label(__('admin.fields.names'))->columns(4)->schema([
                TextEntry::make('locale')->label(__('admin.fields.locale')),
                TextEntry::make('name')->label(__('admin.fields.name')),
                TextEntry::make('type')->label(__('admin.fields.type')),
                TextEntry::make('region')->label(__('admin.fields.region'))->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('slug')->label(__('admin.fields.slug'))->searchable(),
                TextColumn::make('isco_code')->label(__('admin.fields.isco_code'))->searchable(),
                TextColumn::make('publish_state')->label(__('admin.fields.publish_state'))->badge(),
                IconColumn::make('indexable')->label(__('admin.fields.indexable'))->boolean(),
                TextColumn::make('updated_at')->label(__('admin.fields.updated_at'))->dateTime()->timezone('Asia/Dhaka')->sortable(),
            ])
            ->filters([
                SelectFilter::make('publish_state')->label(__('admin.fields.publish_state'))->options(PublishState::class),
            ])
            ->recordActions([ViewAction::make(), ...ListReview::occupationActions()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOccupations::route('/'),
            'view' => ViewOccupation::route('/{record}'),
        ];
    }
}
