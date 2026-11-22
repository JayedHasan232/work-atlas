<?php

namespace App\Modules\Taxonomy\Filament;

use App\Models\User;
use App\Modules\Knowledge\Exceptions\KnowledgeRuleViolation;
use App\Modules\Taxonomy\Actions\ApproveCandidate;
use App\Modules\Taxonomy\Actions\MergeOccupations;
use App\Modules\Taxonomy\Actions\RejectCandidate;
use App\Modules\Taxonomy\Actions\UpdateOccupationNames;
use App\Modules\Taxonomy\Enums\NameType;
use App\Modules\Taxonomy\Enums\PublishState;
use App\Modules\Taxonomy\Exceptions\ListReviewNotAllowed;
use App\Modules\Taxonomy\Models\Occupation;
use App\Modules\Taxonomy\Models\OccupationName;
use Closure;
use Illuminate\Support\Facades\DB;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Utilities\Get;

/**
 * The list-review actions on an occupation (PLAN M11): approve, reject, edit names, merge or mark as
 * a specialization. Each calls a Taxonomy action; a broken rule is shown, and nothing is written.
 */
final class ListReview
{
    public const array DIVISIONS = ['barishal', 'chattogram', 'dhaka', 'khulna', 'mymensingh', 'rajshahi', 'rangpur', 'sylhet'];

    /**
     * @return list<Action>
     */
    public static function occupationActions(): array
    {
        return [self::approve(), self::reject(), self::editNames(), self::merge()];
    }

    public static function rule(): Callout
    {
        return Callout::make(__('admin.review.rule_heading'))->description(__('admin.review.rule'))->info();
    }

    /**
     * Runs a review step and reports the outcome.
     */
    public static function attempt(Closure $step): void
    {
        try {
            $step(self::user());
        } catch (ListReviewNotAllowed|KnowledgeRuleViolation $exception) {
            Notification::make()->danger()->title(__('admin.review.failed'))->body($exception->getMessage())->send();

            return;
        }

        Notification::make()->success()->title(__('admin.review.done'))->send();
    }

    private static function approve(): Action
    {
        return Action::make('approve')
            ->label(__('admin.review.approve'))
            ->modalHeading(__('admin.review.approve_heading'))
            ->color('success')
            ->visible(fn (Occupation $record): bool => in_array($record->publish_state, [PublishState::Candidate, PublishState::Rejected], true))
            ->schema([self::rule(), Textarea::make('note')->label(__('admin.review.note'))->maxLength(2000)])
            ->action(fn (Occupation $record, array $data) => self::attempt(
                fn (User $user) => app(ApproveCandidate::class)->handle($record->id, $user, self::text($data['note'] ?? null)),
            ));
    }

    private static function reject(): Action
    {
        return Action::make('reject')
            ->label(__('admin.review.reject'))
            ->modalHeading(__('admin.review.reject_heading'))
            ->color('danger')
            ->visible(fn (Occupation $record): bool => $record->publish_state === PublishState::Candidate)
            ->schema([self::rule(), Textarea::make('note')->label(__('admin.review.reason'))->required()->maxLength(2000)])
            ->action(fn (Occupation $record, array $data) => self::attempt(
                fn (User $user) => app(RejectCandidate::class)->handle($record->id, $user, (string) ($data['note'] ?? '')),
            ));
    }

    private static function editNames(): Action
    {
        return Action::make('editNames')
            ->label(__('admin.review.edit_names'))
            ->visible(fn (Occupation $record): bool => self::open($record))
            ->fillForm(fn (Occupation $record): array => ['bn' => self::namesOf($record, 'bn'), 'en' => self::namesOf($record, 'en')])
            ->schema([
                self::rule(),
                self::namesRepeater('bn', __('admin.review.names_bn')),
                self::namesRepeater('en', __('admin.review.names_en')),
            ])
            // Both languages or neither: a rule broken in the second must not leave the first saved.
            ->action(fn (Occupation $record, array $data) => self::attempt(fn (User $user) => DB::transaction(function () use ($record, $data, $user): void {
                foreach (['bn', 'en'] as $locale) {
                    $names = self::cleanNames(is_array($data[$locale] ?? null) ? $data[$locale] : []);

                    if ($names !== [] && $names !== self::namesOf($record, $locale)) {
                        app(UpdateOccupationNames::class)->handle($record->id, $locale, $names, $user, __('admin.review.name_comment'));
                    }
                }
            })));
    }

    private static function merge(): Action
    {
        return Action::make('merge')
            ->label(__('admin.review.merge'))
            ->modalHeading(__('admin.review.merge_heading'))
            ->color('warning')
            ->visible(fn (Occupation $record): bool => self::open($record))
            ->schema(fn (Occupation $record): array => [
                self::rule(),
                Select::make('into')
                    ->label(__('admin.review.merge_target'))
                    ->required()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Occupation::query()
                        ->whereKeyNot($record->id)
                        ->whereNotIn('publish_state', [PublishState::Merged, PublishState::Rejected])
                        ->where(fn ($query) => $query->where('slug', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%')
                            ->orWhereHas('names', fn ($names) => $names->where('name', 'ilike', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%')))
                        ->orderBy('slug')
                        ->limit(20)
                        ->pluck('slug', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn (mixed $value): ?string => is_numeric($value) ? Occupation::query()->whereKey((int) $value)->value('slug') : null),
                Toggle::make('specialization')->label(__('admin.review.as_specialization')),
                Textarea::make('note')->label(__('admin.review.note'))->maxLength(2000),
            ])
            ->action(fn (Occupation $record, array $data) => self::attempt(function (User $user) use ($record, $data): void {
                $note = self::text($data['note'] ?? null);

                if ((bool) ($data['specialization'] ?? false)) {
                    $note = trim(__('admin.review.specialization_note').' '.($note ?? ''));
                }

                app(MergeOccupations::class)->handle($record->id, (int) $data['into'], $user, $note);
            }));
    }

    private static function namesRepeater(string $locale, string $label): Repeater
    {
        return Repeater::make($locale)
            ->label($label)
            ->columns(3)
            ->reorderable()
            ->defaultItems(0)
            ->schema([
                TextInput::make('name')->label(__('admin.fields.name'))->required()->maxLength(200),
                Select::make('type')
                    ->label(__('admin.fields.type'))
                    ->required()
                    ->live()
                    ->options(collect(NameType::cases())->mapWithKeys(fn (NameType $type) => [$type->value => __("admin.name_types.{$type->value}")])->all()),
                Select::make('region')
                    ->label(__('admin.fields.region'))
                    ->visible(fn (Get $get): bool => $get('type') === NameType::Regional->value)
                    ->required(fn (Get $get): bool => $get('type') === NameType::Regional->value)
                    ->options(collect(self::DIVISIONS)->mapWithKeys(fn (string $division) => [$division => __("admin.divisions.{$division}")])->all()),
            ]);
    }

    /**
     * @return list<array<string, string>>
     */
    private static function namesOf(Occupation $record, string $locale): array
    {
        return array_values(OccupationName::query()
            ->where('occupation_id', $record->id)->where('locale', $locale)->orderBy('id')->get()
            ->map(fn (OccupationName $name): array => array_filter(
                ['name' => $name->name, 'type' => $name->type->value, 'region' => $name->region],
                fn (?string $value): bool => $value !== null,
            ))->all());
    }

    /**
     * @param  array<array-key, mixed>  $items
     * @return list<array<string, string>>
     */
    private static function cleanNames(array $items): array
    {
        $names = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['name'] ?? null) || ! is_string($item['type'] ?? null)) {
                continue;
            }

            $name = ['name' => $item['name'], 'type' => $item['type']];

            if ($item['type'] === NameType::Regional->value && is_string($item['region'] ?? null)) {
                $name['region'] = $item['region'];
            }

            $names[] = $name;
        }

        return $names;
    }

    private static function open(Occupation $record): bool
    {
        return ! in_array($record->publish_state, [PublishState::Merged, PublishState::Rejected], true);
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function user(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw ListReviewNotAllowed::because('Sign in first.');
        }

        return $user;
    }
}
