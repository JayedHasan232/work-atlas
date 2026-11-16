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

See [the module rules](../README.md).
