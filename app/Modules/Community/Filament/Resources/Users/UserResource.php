<?php

namespace App\Modules\Community\Filament\Resources\Users;

use App\Models\User;
use App\Modules\Community\Actions\BlockUser;
use App\Modules\Community\Enums\Role;
use App\Modules\Community\Filament\Resources\Users\Pages\ListUsers;
use App\Modules\Community\Filament\Resources\Users\Pages\ViewUser;
use App\Support\Filament\ReadOnlyResource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * People with an account, for maintainers (PLAN M18, D69): role, contribution counts and the block
 * state. Blocking and unblocking go through BlockUser; roles change only with `atlas:user:promote`.
 */
final class UserResource extends ReadOnlyResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModelLabel(): string
    {
        return __('admin.users.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('admin.fields.id'))->sortable(),
                TextColumn::make('name')->label(__('admin.users.name'))->searchable(),
                TextColumn::make('email')->label(__('admin.users.email'))->searchable(),
                TextColumn::make('role')
                    ->label(__('admin.users.role'))
                    ->badge()
                    ->formatStateUsing(fn (Role $state): string => __("admin.users.roles.{$state->value}")),
                TextColumn::make('accepted_proposals_count')->label(__('admin.users.accepted'))->sortable(),
                TextColumn::make('blocked_at')->label(__('admin.users.blocked_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
                TextColumn::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('admin.users.role'))
                    ->options(collect(Role::cases())->mapWithKeys(fn (Role $role): array => [
                        $role->value => __("admin.users.roles.{$role->value}"),
                    ])->all()),
                TernaryFilter::make('blocked')
                    ->label(__('admin.users.blocked'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('blocked_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('blocked_at'),
                    ),
            ])
            ->recordActions(self::blockActions());
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')->label(__('admin.users.name')),
            TextEntry::make('email')->label(__('admin.users.email')),
            TextEntry::make('role')
                ->label(__('admin.users.role'))
                ->badge()
                ->formatStateUsing(fn (Role $state): string => __("admin.users.roles.{$state->value}")),
            TextEntry::make('accepted_proposals_count')->label(__('admin.users.accepted')),
            TextEntry::make('rejected_proposals_count')->label(__('admin.users.rejected')),
            TextEntry::make('confirmed_answers_count')->label(__('admin.users.confirmed_answers')),
            TextEntry::make('blocked_at')->label(__('admin.users.blocked_at'))->dateTime()->timezone('Asia/Dhaka')->placeholder('—'),
            TextEntry::make('blocked_by')->label(__('admin.users.blocked_by'))->placeholder('—'),
            TextEntry::make('block_reason')->label(__('admin.users.block_reason'))->placeholder('—')->columnSpanFull(),
            TextEntry::make('created_at')->label(__('admin.fields.created_at'))->dateTime()->timezone('Asia/Dhaka'),
        ]);
    }

    /**
     * Block (with a reason) or unblock, in the list and on the person's page.
     *
     * @return list<Action>
     */
    public static function blockActions(): array
    {
        return [
            Action::make('block')
                ->label(__('admin.users.actions.block'))
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label(__('admin.users.block_reason'))->maxLength(2000),
                ])
                ->visible(fn (User $record): bool => ! $record->isBlocked() && $record->id !== Auth::id())
                ->action(function (User $record, array $data): void {
                    $reason = $data['reason'] ?? null;
                    app(BlockUser::class)->handle($record->id, self::maintainer(), is_string($reason) && $reason !== '' ? $reason : null);
                }),
            Action::make('unblock')
                ->label(__('admin.users.actions.unblock'))
                ->requiresConfirmation()
                ->visible(fn (User $record): bool => $record->isBlocked())
                ->action(fn (User $record) => app(BlockUser::class)->unblock($record->id, self::maintainer())),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }

    private static function maintainer(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
