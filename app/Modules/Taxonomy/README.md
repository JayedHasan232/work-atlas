# Taxonomy

The list of occupations and how they are named.

- **Owns:** occupations (slug, ISCO-08 code, BSCO-2012 code, group, publish state), ISCO/BSCO groups,
  occupation names (primary, alias, local, regional; per locale and region), merges.
- **Rules:** the list grows one ISCO-08 unit group at a time, including local and regional names and
  informal, self-employed, gig, seasonal, care and household work. Occupation or specialization follows
  `docs/policies/separate-occupation.md`. A human decides every merge; merged names become aliases so
  search still finds them (বিদ্যুৎ মিস্ত্রি, নলমিস্ত্রি).
- **Sources:** `data/taxonomy/` with `SOURCE.md` (URL, license, accessed date).
- **Import (M6):** `Actions\ImportIscoGroups` reads `data/taxonomy/isco08.csv` (`code,title_en`) and
  records its source through `Knowledge\Actions\RecordSource`. Run it with
  `php artisan atlas:taxonomy:import`; a second run changes nothing (PLAN D29).
- **Names (D44):** an occupation's names are the Knowledge field `names` (one list per language), so
  they have revisions and statuses. `occupation_names` is the search copy: `Actions\SyncOccupationNames`
  rewrites it from `Knowledge\Events\CurrentValueChanged` (listener in `AppServiceProvider`), with
  `NormalizeName` as the search key.
- **List expansion (M10):** `Actions\ExpandUnitGroup` asks the AI (prompt
  `taxonomy/expand-unit-group`, built by `BuildExpansionRequest`) which occupations belong to one unit
  group; `ApplyExpansion` writes them as `candidate` occupations linked to the run, with their names as
  `ai_draft`, all or nothing. A rerun with the same input reuses the earlier run (D45).
  `php artisan atlas:taxonomy:expand --groups=7411,7412 --dry-run` prints the estimate;
  without `--dry-run` it checks the budget cap and queues `Jobs\ExpandUnitGroupJob` per group.
- **List review (M11):** `Actions\DetectDuplicates` (`php artisan atlas:taxonomy:duplicates`) stores
  possible duplicate pairs (PLAN D47). A reviewer approves (`ApproveCandidate`), rejects
  (`RejectCandidate`), corrects names (`UpdateOccupationNames`), merges (`MergeOccupations`, D48) or
  keeps a pair separate (`KeepSeparate`), from the Filament screens in `Filament/`.
  `Queries\OccupationBySlug` follows a merge to the canonical slug (D49).
- **Browsing (M13):** `Queries\PublicOccupations` (listed and published only, by primary name) and
  `Queries\IscoGroups` (a group, its path, its children with counts) feed the public pages.

See [the module rules](../README.md).
