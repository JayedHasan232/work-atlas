# Occupation, specialization or alias

> **Status: draft, waiting for the maintainer's approval (PLAN H4).** The rule is decision A4; the
> examples were drafted in M7. Until they are approved, list review (M10, M11) treats them as guidance.

Every name someone uses for their work should lead to one occupation page. This page says when a name
is its own occupation, when it is a specialization of another, and when it is just another name.

## The rule (A4)

1. **Separate occupation** when the **main tasks** or the **training** differ substantially: a person
   who can do one could not do the other without learning a new trade.
2. **Specialization** when the main tasks and the training are mostly the same and only the material,
   the customer or the product differs. A specialization lives on the occupation's page.
3. **Alias** when it is the same work under another name: a local or regional name, an English or
   borrowed word, a spelling, or a gendered form. Aliases are kept so search finds them
   (বিদ্যুৎ মিস্ত্রি, নলমিস্ত্রি).

Not reasons to split, at least for now:

- **Income or seniority.** Junior and senior are a career path, not two occupations. Splits by income
  may come later, once income data with sources exists (A4).
- **How the work is arranged.** Employed, self-employed, freelance, daily wage, part-time or seasonal
  are fields of the occupation (`work_time`, `part_time_possible`, `seasonal`), not new occupations.
- **Who does it.** Gender, age or background never make a separate occupation. Gendered names are
  aliases; the primary name is gender-neutral.

Where ISCO-08 already puts two kinds of work in different unit groups, they stay separate. Inside one
unit group, the rule above decides. A human decides every merge or split; merged names become aliases.

## How to decide

Ask, in order:

1. Is it the same work under another name? → alias.
2. Would a person doing one need to learn a new trade to do the other (main tasks or training)? →
   separate occupation.
3. Otherwise → specialization of the broader occupation.

If a name covers several kinds of work (for example "garment worker"), it is not an occupation by
itself: split it into the kinds of work and keep the broad name as an alias of the most common one,
with a note.

## Primary names are respectful

The primary name is the plain, respectful name the workers themselves would accept. A common name that
some people find belittling may be kept as an alias, only so search finds it, and is never shown as the
title (rule 1).

## Examples

| # | Names | Decision | Why |
|---|---|---|---|
| 1 | বিদ্যুৎ মিস্ত্রি, ইলেকট্রিশিয়ান (house wiring electrician) | One occupation; the other names are aliases | Same work under local and borrowed names. |
| 2 | House wiring electrician · power line worker (বিদ্যুৎ লাইনের কর্মী) | Separate | Line work on poles and high-voltage lines needs different training and safety skills. |
| 3 | নলমিস্ত্রি, প্লাম্বার, পানির মিস্ত্রি | One occupation; aliases | Same work; names differ by region and language. |
| 4 | দর্জি (tailor) · sewing machine operator in a garment factory | Separate | A tailor measures, cuts and makes a whole garment for a customer; an operator sews one part on a line. Tasks and training differ. |
| 5 | Ladies' tailor · gents' tailor | Specializations of tailor | Same core tasks and training; only the garments differ. |
| 6 | "Garment worker" (গার্মেন্টস কর্মী) | Not one occupation | Covers sewing, cutting, finishing and quality checking, which differ in tasks. Split them; keep the broad name as an alias with a note. |
| 7 | Pedal rickshaw driver · auto-rickshaw driver | Separate | Driving a motor vehicle needs different skills and training from pedalling a rickshaw. |
| 8 | Battery rickshaw (ইজিবাইক) driver · auto-rickshaw driver | Decide in list review | Close: both drive small passenger motor vehicles. Keep separate only if reviewers find the training differs substantially. |
| 9 | Farmer who runs their own farm · farm day labourer (কৃষি শ্রমিক) | Separate | The farmer plans, buys inputs and sells the crop; the labourer does field tasks for a daily wage. Main tasks differ. |
| 10 | Rice farmer · jute farmer | Specializations of field crop farmer | Same kind of field work and training; only the crop differs. Seasons are recorded in `seasonal`. |
| 11 | River or sea fisher · pond fish farmer | Separate | Catching wild fish and raising fish in ponds take different tasks and skills. |
| 12 | গৃহকর্মী (domestic worker) and other names for it | One occupation; other names are aliases | Same work. The primary name is গৃহকর্মী; names some workers find belittling stay aliases for search only. |
| 13 | Live-in domestic worker · part-time domestic worker | Specializations, or one occupation | Same tasks; the arrangement is a field (`work_time`, `part_time_possible`), not a new occupation. |
| 14 | Restaurant cook · event cook (বাবুর্চি for weddings and other events) | Specializations of cook | Same core cooking skills; event work is seasonal (winter wedding season, Eid), which `seasonal` records. |
| 15 | Nurse · midwife | Separate | Different training and different main tasks. |
| 16 | School teacher · private tutor (গৃহশিক্ষক) | Separate | Both teach, but tutors usually have no teacher training and work one-to-one outside a school. Training and setting differ. |
| 17 | Freelance graphic designer · employed graphic designer | One occupation | Same work; freelance or employed is how the work is arranged. |
| 18 | Motorcycle passenger rider (app-based) · motorcycle delivery rider | Separate | Carrying passengers and delivering goods are different main tasks. |
| 19 | রাজমিস্ত্রি (mason) · construction helper (নির্মাণ সহকারী, জোগালি) | Separate | The mason lays brick and plaster, a skilled trade; the helper carries and mixes materials. Main tasks and training differ. |
| 20 | Salesman · saleswoman · salesperson (বিক্রয়কর্মী) | One occupation; gendered names are aliases | Gender never makes a separate occupation. The primary name is gender-neutral. |

Names of companies or apps are never part of an occupation name.

## Where this is used

- M10 (list expansion): the AI proposes names per ISCO-08 unit group, marked occupation,
  specialization or alias, with this rule and these examples in the prompt.
- M11 (duplicate detection and list review): reviewers apply the rule; a human decides every merge.
