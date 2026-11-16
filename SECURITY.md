# Security and privacy policy

> **বাংলা:** নিরাপত্তা বা গোপনীয়তার সমস্যা পেলে প্রকাশ্য issue খুলবেন না। GitHub-এর repo পাতার
> **Security → Report a vulnerability** দিয়ে গোপনে জানান। কারও ব্যক্তিগত তথ্য ফাঁস হওয়া, গোপন আয়ের
> তথ্য থেকে মানুষ চেনা যাওয়া, বা যাচাই না হওয়া স্বাস্থ্য/আয়ের তথ্য প্রকাশ্যে দেখা যাওয়া — এগুলোও
> নিরাপত্তা সমস্যা।

## Reporting

Please **don't open a public issue** for a security or privacy problem.

Report it privately through GitHub: on the repository page, open **Security → Report a vulnerability**.
Include what you found, how to reproduce it, and what an attacker could do with it. Don't access, change
or keep other people's data beyond what you need to show the problem.

We aim to reply within 7 days. Work Atlas is run by volunteers and has no bug bounty, but we credit
reporters who want to be named.

## What counts

Besides the usual security bugs (authentication, access control, injection, cross-site scripting,
leaked secrets), these are treated as security problems too:

- Personal data exposed anywhere public: pages, the API, open data releases, logs.
- A way to identify a person from private income or experience submissions, or from aggregates built
  from too few submissions.
- A sensitive field (income, health, legal) shown publicly before it is verified.
- A way to change data without going through the revision history, or to change a past revision.
- A way to make the AI pipeline overwrite a verified value.

## Supported versions

Only the `main` branch and the live site are supported.
