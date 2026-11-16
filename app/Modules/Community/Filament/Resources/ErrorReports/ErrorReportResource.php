<?php

namespace App\Modules\Community\Filament\Resources\ErrorReports;

use App\Modules\Community\Actions\HandleErrorReport;
use App\Modules\Community\Enums\ErrorReportState;
use App\Modules\Community\Filament\Resources\ErrorReports\Pages\ListErrorReports;
use App\Modules\Community\Filament\Resources\ErrorReports\Pages\ViewErrorReport;
use App\Modules\Community\Models\ErrorReport;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * The moderation queue for error reports (PLAN M16): open reports first, newest first. A maintainer
 * resolves, dismisses or marks one as spam through HandleErrorReport; nothing else changes a report.
 */
final class ErrorReportResource extends ReadOnlyResource
{
    protected static ?string $model = ErrorReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function getModelLabel(): string
    {
        return __('admin.error_reports.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.error_reports.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('occupation_id')->label(__('admin.occupations.singular')),
                TextColumn::make('field_definition_id')->label(__('admin.fields.field'))->placeholder(__('admin.error_reports.whole_page')),
                TextColumn::make('message')->label(__('admin.error_reports.message'))->wrap()->limit(300),
                TextColumn::make('locale')->label(__('admin.fields.locale')),
                TextColumn::make('user_id')->label(__('admin.error_reports.reporter'))->placeholder(__('admin.error_reports.anonymous')),
                TextColumn::make('state')
                    ->label(__('admin.error_reports.state'))
                    ->badge()
                    ->formatStateUsing(fn (ErrorReportState $state): string => __("admin.error_reports.states.{$state->value}")),
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label(__('admin.error_reports.state'))
                    ->options(collect(ErrorReportState::cases())->mapWithKeys(fn (ErrorReportState $state): array => [
                        $state->value => __("admin.error_reports.states.{$state->value}"),
                    ])->all())
                    ->default(ErrorReportState::Open->value),
            ])
            ->recordActions(self::handleActions());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('occupation_id')->label(__('admin.occupations.singular')),
            TextEntry::make('field_definition_id')->label(__('admin.fields.field'))->placeholder(__('admin.error_reports.whole_page')),
            TextEntry::make('locale')->label(__('admin.fields.locale')),
            TextEntry::make('message')->label(__('admin.error_reports.message'))->columnSpanFull(),
            TextEntry::make('user_id')->label(__('admin.error_reports.reporter'))->placeholder(__('admin.error_reports.anonymous')),
            TextEntry::make('state')
                ->label(__('admin.error_reports.state'))
                ->badge()
                ->formatStateUsing(fn (ErrorReportState $state): string => __("admin.error_reports.states.{$state->value}")),
            TextEntry::make('handled_by')->label(__('admin.error_reports.handled_by'))->placeholder('—'),
            TextEntry::make('handled_at')->label(__('admin.error_reports.handled_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka'),
        ]);
    }

    /**
     * Resolve, dismiss or mark as spam, while the report is open: in the list and on the report.
     *
     * @return list<Action>
     */
    public static function handleActions(): array
    {
        return array_map(fn (ErrorReportState $state): Action => Action::make($state->value)
            ->label(__("admin.error_reports.actions.{$state->value}"))
            ->requiresConfirmation()
            ->visible(fn (ErrorReport $record): bool => $record->state === ErrorReportState::Open)
            ->action(fn (ErrorReport $record) => app(HandleErrorReport::class)->handle(
                $record->id,
                (int) Auth::id(),
                $state,
            )), [ErrorReportState::Resolved, ErrorReportState::Dismissed, ErrorReportState::Spam]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListErrorReports::route('/'),
            'view' => ViewErrorReport::route('/{record}'),
        ];
    }
}
