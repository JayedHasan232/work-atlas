<?php

namespace App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates;

use App\Models\User;
use App\Modules\Taxonomy\Actions\KeepSeparate;
use App\Modules\Taxonomy\Actions\MergeOccupations;
use App\Modules\Taxonomy\Enums\DuplicateStatus;
use App\Modules\Taxonomy\Filament\ListReview;
use App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\Pages\ListDuplicateCandidates;
use App\Modules\Taxonomy\Filament\Resources\DuplicateCandidates\Pages\ViewDuplicateCandidate;
use App\Modules\Taxonomy\Models\DuplicateCandidate;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Possible duplicate occupations (M11, D47). A reviewer merges one into the other or keeps them
 * separate; a pair kept separate is never flagged again.
 */
final class DuplicateCandidateResource extends ReadOnlyResource
{
    protected static ?string $model = DuplicateCandidate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    public static function getModelLabel(): string
    {
        return __('admin.duplicates.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.duplicates.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = DuplicateCandidate::query()->where('status', DuplicateStatus::Open)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('occupation.slug')->label(__('admin.duplicates.first')),
            TextEntry::make('otherOccupation.slug')->label(__('admin.duplicates.second')),
            TextEntry::make('locale')->label(__('admin.fields.locale')),
            TextEntry::make('name')->label(__('admin.duplicates.first_name')),
            TextEntry::make('other_name')->label(__('admin.duplicates.second_name')),
            TextEntry::make('similarity')->label(__('admin.duplicates.similarity')),
            TextEntry::make('status')->label(__('admin.fields.status'))->badge()->formatStateUsing(self::statusLabel(...)),
            TextEntry::make('decided_by_id')->label(__('admin.duplicates.decided_by'))->placeholder('—'),
            TextEntry::make('decided_at')->label(__('admin.duplicates.decided_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('decision_note')->label(__('admin.review.note'))->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('similarity', 'desc')
            ->columns([
                TextColumn::make('occupation.slug')->label(__('admin.duplicates.first')),
                TextColumn::make('name')->label(__('admin.duplicates.first_name'))->searchable(),
                TextColumn::make('otherOccupation.slug')->label(__('admin.duplicates.second')),
                TextColumn::make('other_name')->label(__('admin.duplicates.second_name'))->searchable(),
                TextColumn::make('locale')->label(__('admin.fields.locale')),
                TextColumn::make('similarity')->label(__('admin.duplicates.similarity'))->sortable(),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge()->formatStateUsing(self::statusLabel(...)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.fields.status'))
                    ->options(collect(DuplicateStatus::cases())->mapWithKeys(fn (DuplicateStatus $status) => [$status->value => self::statusLabel($status)])->all())
                    ->default(DuplicateStatus::Open->value),
            ])
            ->recordActions([ViewAction::make(), ...self::decisionActions()]);
    }

    /**
     * @return list<Action>
     */
    public static function decisionActions(): array
    {
        return [
            self::mergeAction('mergeFirst', __('admin.duplicates.merge_first'), first: true),
            self::mergeAction('mergeSecond', __('admin.duplicates.merge_second'), first: false),
            Action::make('keepSeparate')
                ->label(__('admin.duplicates.keep_separate'))
                ->modalHeading(__('admin.duplicates.keep_separate_heading'))
                ->visible(fn (DuplicateCandidate $record): bool => $record->status === DuplicateStatus::Open)
                ->schema([ListReview::rule(), Textarea::make('note')->label(__('admin.review.note'))->maxLength(2000)])
                ->action(fn (DuplicateCandidate $record, array $data) => ListReview::attempt(
                    fn (User $user) => app(KeepSeparate::class)->handle($record->id, $user, self::text($data['note'] ?? null)),
                )),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDuplicateCandidates::route('/'),
            'view' => ViewDuplicateCandidate::route('/{record}'),
        ];
    }

    private static function mergeAction(string $name, string $label, bool $first): Action
    {
        return Action::make($name)
            ->label($label)
            ->modalHeading(__('admin.review.merge_heading'))
            ->color('warning')
            ->visible(fn (DuplicateCandidate $record): bool => $record->status === DuplicateStatus::Open)
            ->schema([ListReview::rule(), Textarea::make('note')->label(__('admin.review.note'))->maxLength(2000)])
            ->action(fn (DuplicateCandidate $record, array $data) => ListReview::attempt(fn (User $user) => app(MergeOccupations::class)->handle(
                $first ? $record->occupation_id : $record->other_occupation_id,
                $first ? $record->other_occupation_id : $record->occupation_id,
                $user,
                self::text($data['note'] ?? null),
            )));
    }

    private static function statusLabel(mixed $state): string
    {
        $value = $state instanceof DuplicateStatus ? $state->value : (string) $state;

        return __("admin.duplicates.statuses.{$value}");
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
