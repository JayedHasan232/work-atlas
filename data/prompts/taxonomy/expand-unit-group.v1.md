---
version: 1
schema: taxonomy/expand-unit-group.v1.json
max_output_tokens: 8000
temperature: 0.2
variables: [isco_code, group_title_en, group_title_bn, hierarchy, existing_occupations]
---
You help build Work Atlas, an open, nonprofit database of every occupation in Bangladesh. People who
are looking for work use it, many of them with little formal education, so it must include all
honest work, formal and informal.

## Your task

List the occupations that people in Bangladesh do inside one ISCO-08 unit group, with their names in
Bangla and English. Your answer is a draft: reviewers check every entry before anything is published.

- ISCO-08 unit group: {{ isco_code }}
- English title: {{ group_title_en }}
- Bangla title: {{ group_title_bn }}
- Where it sits in ISCO-08: {{ hierarchy }}

Occupations already in the list for this group (do not repeat them; you may add a missing name of
theirs only by listing them again with the same primary names):
{{ existing_occupations }}

## What belongs in the list

Include every kind of work in this unit group that people really do in Bangladesh: employed and
self-employed, informal and daily-wage work, platform (gig) work, seasonal work, care work, household
work and unpaid family work. Do not include work that belongs to a different ISCO-08 unit group. If
nobody in Bangladesh does work in this group, return an empty list and say so in `note`.

## Occupation, specialization or name (the separate-occupation rule)

1. Same work under another name (a local or regional name, an English or borrowed word, a spelling,
   a gendered form) → a name of the same occupation, never a new occupation.
2. A person doing one would need to learn a new trade to do the other (different main tasks or
   training) → separate occupations.
3. Otherwise the main tasks and training are mostly the same and only the material, customer or
   product differs → a specialization of the broader occupation (list it in `specializations`).

Never a reason to split: income or seniority (junior and senior are a career path); how the work is
arranged (employed, freelance, daily wage, part-time, seasonal: put that in `work_arrangements`); who
does it (gender, age or background). A broad name that covers several kinds of work ("garment worker")
is not one occupation: list the kinds of work and add the broad name as an alias of the most common.

Examples: বিদ্যুৎ মিস্ত্রি and ইলেকট্রিশিয়ান are one occupation (house wiring electrician); a power
line worker is separate (different training). নলমিস্ত্রি, প্লাম্বার and পানির মিস্ত্রি are one
occupation. A tailor and a sewing machine operator in a garment factory are separate. Ladies' and
gents' tailors are specializations of tailor. A pedal rickshaw driver and an auto-rickshaw driver are
separate. A salesman and a saleswoman are one occupation, salesperson (বিক্রয়কর্মী).

## Names

For each occupation give `names_bn` and `names_en`, each with exactly one `primary` name, then the
other names people use:

- `primary`: the plain, respectful name the workers themselves would accept, gender-neutral. A common
  name that some workers find belittling may appear only as an `alias`, never as the primary name.
- `alias`: another name for the same work, a borrowed word, a spelling or a gendered form.
- `local`: a name used in everyday speech across Bangladesh.
- `regional`: a name used mainly in one division; give `region` as one of barishal, chattogram,
  dhaka, khulna, mymensingh, rajshahi, rangpur, sylhet.

Write Bangla names in Bangla script as people say them. Never use company, brand or app names. Do not
invent names: list only names you know are used.

## Other fields

- `specializations`: kinds of the occupation that differ only in material, customer or product, each
  with a Bangla and an English name. They will be kept as names of this occupation.
- `work_arrangements`: how this work is usually done in Bangladesh, as any of formal_employment,
  informal_employment, self_employed, daily_wage, gig_platform, seasonal, household, unpaid_family.
- `separate_because`: one or two sentences, in English, on why this is its own occupation and not a
  specialization or a name of another one in the list.
- `confidence`: from 0 to 1, how sure you are that this occupation exists in Bangladesh as described.
- `note`: anything a reviewer should know about the group as a whole, or an empty string.

Do not give numbers of any kind: no incomes, worker counts or statistics. Answer with one JSON object
that matches the schema, and nothing else.
