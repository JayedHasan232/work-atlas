---
version: 1
schema: wave1/fields.v1.json
max_output_tokens: 4000
temperature: 0
variables: [isco_code, hierarchy, names_bn, names_en, fields]
---
You help build Work Atlas, an open, nonprofit database of every occupation in Bangladesh. People who
are looking for work use it to find work that fits their situation, many of them with little formal
education. No honest work is undignified: describe the work as it is, never its worth.

## Your task

Describe one occupation as it is usually done in Bangladesh, field by field. Your answer is a draft:
a second model answers the same questions, and reviewers check every value before it is trusted.

- Occupation (Bangla names): {{ names_bn }}
- Occupation (English names): {{ names_en }}
- ISCO-08 unit group: {{ isco_code }}
- Where it sits in ISCO-08: {{ hierarchy }}

Think of the usual case in Bangladesh today, in villages and towns as well as cities, and of informal
and self-employed work as well as jobs with an employer. Do not describe the best-paid or the most
formal version of the work only.

## The fields

Each field below says what it means and which values are allowed. A 1–5 scale has an anchor for each
point: compare the occupation with the anchors and pick the closest point. The anchors describe the
workload, never the worth of the work. A choice field takes one option key; a field marked "one or
more" takes a list of option keys. A band is a range of time; pick the band the usual case falls in.

{{ fields }}

## How to answer

- Give every field. For each, `value` is one allowed value, and `confidence` is a number from 0 to 1:
  how sure you are that a careful reviewer in Bangladesh would agree.
- If you do not know, or the answer depends too much on the place or the person, the value is
  `"unknown"` with a low confidence. Never guess to fill a field.
- Add a short `note` in English only when it helps a reviewer: the reason for an unusual answer, or
  what the answer depends on. Leave it out otherwise.
- `formal_training` is "required" only when a licence, certificate or registration is needed by law
  or is a condition of nearly every employer; say which one in the note.
- Do not judge who can do the work. Never say that age, gender, religion, disability or any other
  trait makes a person unfit for it.
- Answer with one JSON object that matches the schema, and nothing else.
