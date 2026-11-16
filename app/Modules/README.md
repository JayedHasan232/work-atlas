# Modules

Work Atlas is one Laravel app split into modules. Each module owns its tables and its rules; the
others reach it only through its public classes.

| Module | Owns |
|---|---|
| [Taxonomy](Taxonomy/README.md) | occupations, ISCO-08/BSCO-2012 groups, names, aliases, merges |
| [Knowledge](Knowledge/README.md) | field definitions, field values, revisions, sources (the one write path) |
| [Observations](Observations/README.md) | income, demand and cost by region, period and source; private submissions |
| [Community](Community/README.md) | roles, trust levels, verification tasks, discussions, credit |
| [AiPipeline](AiPipeline/README.md) | `AiGateway`, `ai_runs`, prompt registry, drafts, cross-checks |
| [Discovery](Discovery/README.md) | guided filter, compare, later recommendations |
| [Publishing](Publishing/README.md) | public presenters, index gate, sitemap, public API, open data releases |

## Layout

```
app/Modules/<Module>/
  Actions/      one public handle() each; every change to data goes through one
  Queries/      reads that other code may call
  Data/         plain data objects passed between modules
  Enums/
  Events/
  Models/       private to the module (Models/Concerns/ for model traits)
  Exceptions/   only when a caller needs to catch a module-specific error
  Jobs/         private to the module
  Policies/     private to the module
  Http/Controllers/{Web,Api}/  Http/Requests/
  Filament/     admin screens for the core team
```

Each kind of class lives only in its folder (`tests/Arch/ModuleLayoutTest.php`, the Laravel arch
preset's rules applied per module). Factories are in `database/factories/<Module>/`; models name
theirs with `#[UseFactory]`.

Classes live in the `App\Modules\<Module>` namespace; the `App\` PSR-4 rule in `composer.json` loads
them, so a new module needs no autoload change.

## Rules

- Business logic lives in Actions and Queries, never in controllers, Filament resources, jobs,
  commands or models. Web, API, Filament and Artisan call the same actions.
- A module uses another module's Actions, Queries, Data, Enums and Events, never its Models, Jobs,
  Policies, Http or Filament classes, and never its tables. Across modules, rows are linked by id
  (with a database foreign key) but not by an Eloquent relation. `tests/Arch/ModuleBoundariesTest.php`
  checks this; add a new module to its list.
- Controllers stay thin: no `DB` facade, no query builders, no module models
  (`tests/Arch/ThinControllersTest.php`).
