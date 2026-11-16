<?php

namespace App\Modules\Knowledge\Filament\Resources\CrossChecks;

use App\Modules\Knowledge\Enums\CrossCheck;
use App\Modules\Knowledge\Filament\Resources\CrossChecks\Pages\ListCrossChecks;
use App\Modules\Knowledge\Filament\Resources\CrossChecks\Pages\ViewCrossCheck;
use App\Modules\Knowledge\Models\FieldValue;
use App\Modules\Knowledge\Models\Revision;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The two-model cross-check of each value (PLAN M12, D50), disagreements first. A reviewer decides
 * a value on its revision; this report only lists and exports.
 */
final class CrossCheckResource extends ReadOnlyResource
{
    protected static ?string $model = FieldValue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function getModelLabel(): string
    {
        return __('admin.cross_checks.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.cross_checks.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('cross_check');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FieldValue::query()->where('cross_check', CrossCheck::Disagreed)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('occupation_id')->label(__('admin.fields.occupation_id')),
            TextEntry::make('definition.key')->label(__('admin.fields.field')),
            TextEntry::make('status')->label(__('admin.fields.status'))->badge(),
            TextEntry::make('cross_check')->label(__('admin.fields.cross_check'))->badge()->formatStateUsing(self::crossCheckLabel(...)),
            TextEntry::make('review_priority')->label(__('admin.fields.review_priority')),
            TextEntry::make('cross_checked_at')->label(__('admin.fields.cross_checked_at'))->dateTime()->timezone('Asia/Dhaka'),
            TextEntry::make('currentRevision.new_value')->label(__('admin.cross_checks.current_value'))->state(fn (FieldValue $record): string => self::show($record->currentRevision?->new_value)),
            TextEntry::make('proposals')->label(__('admin.cross_checks.other_values'))->listWithLineBreaks()->placeholder('—')
                ->state(fn (FieldValue $record): array => $record->revisions()->where('proposed', true)->whereDoesntHave('reviews')->orderBy('id')->get()
                    ->map(fn (Revision $revision): string => self::show($revision->new_value))->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('review_priority', 'desc')
            ->columns([
                TextColumn::make('occupation_id')->label(__('admin.fields.occupation_id'))->sortable(),
                TextColumn::make('definition.key')->label(__('admin.fields.field')),
                TextColumn::make('currentRevision.new_value')->label(__('admin.cross_checks.current_value'))->state(fn (FieldValue $record): string => self::show($record->currentRevision?->new_value)),
                TextColumn::make('status')->label(__('admin.fields.status'))->badge(),
                TextColumn::make('cross_check')->label(__('admin.fields.cross_check'))->badge()->formatStateUsing(self::crossCheckLabel(...)),
                TextColumn::make('review_priority')->label(__('admin.fields.review_priority'))->sortable(),
            ])
            ->filters([
                SelectFilter::make('cross_check')
                    ->label(__('admin.fields.cross_check'))
                    ->options(collect(CrossCheck::cases())->mapWithKeys(fn (CrossCheck $check) => [$check->value => self::crossCheckLabel($check)])->all())
                    ->default(CrossCheck::Disagreed->value),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrossChecks::route('/'),
            'view' => ViewCrossCheck::route('/{record}'),
        ];
    }

    private static function crossCheckLabel(mixed $state): string
    {
        $value = $state instanceof CrossCheck ? $state->value : (string) $state;

        return __("admin.cross_checks.results.{$value}");
    }

    private static function show(mixed $value): string
    {
        return $value === null ? '—' : (is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE));
    }
}
