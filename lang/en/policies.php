<?php

// Policy pages (PLAN M23). DRAFTS: every text here waits for the maintainer's approval (and legal advice
// where needed) before the public launch.
return [
    'title' => 'Policies',
    'intro' => 'How Work Atlas works, what we promise, and what we expect from everyone who takes part.',
    'draft_notice' => 'Draft: this policy is waiting for approval and may change before the public launch.',
    'contact_pending' => 'A contact address for the project will be added here before the public launch. Until then, use "Report" on an occupation page.',
    'all_policies' => 'All policies',
    'terms_summary' => 'What you agree to when you contribute: your contributions are open under CC BY-SA 4.0.',

    'code_of_conduct' => [
        'title' => 'Code of conduct',
        'summary' => 'How we treat each other, and the work and people we write about.',
        'sections' => [
            'work' => [
                'title' => 'Respect every kind of work',
                'body' => 'No honest work is undignified. Do not mock, belittle or rank occupations, or the people who do them.',
            ],
            'people' => [
                'title' => 'Respect people',
                'body' => 'Be kind and patient. No harassment, threats or insults, and no attacks on anyone for their religion, gender, ethnicity, caste, disability, age, region or language.',
            ],
            'good_faith' => [
                'title' => 'Assume good faith',
                'body' => 'Most people here want to make the atlas better. Disagree about facts with sources and reasons on the talk page, never with personal attacks.',
            ],
            'honesty' => [
                'title' => 'Be honest',
                'body' => 'Do not add false information, made-up sources or advertising. If you have a conflict of interest, for example you run a training centre, say so when you contribute.',
            ],
            'privacy' => [
                'title' => 'Protect the privacy of others',
                'body' => 'Never post anyone else\'s personal information, such as a phone number, address, income or ID.',
            ],
            'enforcement' => [
                'title' => 'When something goes wrong',
                'body' => 'Use "Report" on an occupation page, or the talk page. Moderators may hide posts, undo edits or limit and block accounts. Serious or repeated harm leads to a block. You can ask the maintainers to review a decision.',
            ],
        ],
    ],

    'editorial' => [
        'title' => 'Editorial policy',
        'summary' => 'How information gets into the atlas, how it is checked, and how mistakes are fixed.',
        'sections' => [
            'status' => [
                'title' => 'Every value shows how far it has been checked',
                'body' => 'Each value has a status: AI draft, sourced, supported by the community, verified, or disputed. The status is shown next to the value.',
            ],
            'ai' => [
                'title' => 'AI writes drafts, people check them',
                'body' => 'AI drafts are marked as drafts. AI never replaces a verified value; it can only propose a change for people to review.',
            ],
            'numbers' => [
                'title' => 'No number without a source',
                'body' => 'Income, demand, costs, statistics and legal requirements need a source. Without a source the value stays empty; we never fill in a guessed or typical figure. Money is shown as a range with the region, period and source.',
            ],
            'tone' => [
                'title' => 'Plain, respectful and balanced',
                'body' => 'We write in plain language and in a gender-neutral way, without stereotypes. Benefits and difficulties are shown side by side. We show what society thinks next to what is true, and never give occupations a prestige score.',
            ],
            'future' => [
                'title' => 'No promises about the future',
                'body' => 'No work is called safe from AI or safe for the future. Forecasts are labelled as forecasts, with their assumptions. Exposure to AI does not mean a job will disappear.',
            ],
            'corrections' => [
                'title' => 'Corrections',
                'body' => 'Every change is kept in the history. A mistake is fixed with a new change. Anyone can report an error, even without an account.',
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy policy',
        'summary' => 'What we collect, what is public, what stays private, and who helps us run the site.',
        'sections' => [
            'reading' => [
                'title' => 'Reading needs no account',
                'body' => 'You can read every public page, use the guide and the API without signing up.',
            ],
            'account' => [
                'title' => 'If you create an account',
                'body' => 'We keep your name, email address, your password (only in a form that cannot be read back), what you contribute and when, and your choices, such as being credited anonymously.',
            ],
            'public' => [
                'title' => 'What is public',
                'body' => 'Your contributions are public under CC BY-SA 4.0, credited with your name, or as "a contributor" if you chose anonymous credit. Your email address is never shown.',
            ],
            'guide' => [
                'title' => 'The guide does not keep your answers',
                'body' => 'Answers you give in the guide stay in the page address in your browser. We do not store them or link them to your account.',
            ],
            'private' => [
                'title' => 'Private submissions stay private',
                'body' => 'Income or experience you share privately is never published on its own. Only totals from enough people are shown. Private submissions never appear in open data or the API.',
            ],
            'cookies' => [
                'title' => 'Cookies and analytics',
                'body' => 'We use only the cookies needed for signing in and for your settings, such as language and appearance. If visit counting is on, it runs on our own server, without cookies, without tracking you across sites, without page addresses that contain your answers or searches, and it respects Do Not Track.',
            ],
            'errors' => [
                'title' => 'Error reports',
                'body' => 'When something breaks, an error report goes to our error monitor. Names, email addresses, IP addresses and anything typed into forms are removed before it leaves our server.',
            ],
            'providers' => [
                'title' => 'Who helps us run the site',
                'body' => 'We do not sell data and show no ads. We use a hosting company for the server, Cloudflare for delivery and backups, Amazon SES to send sign-in emails, and Sentry for error reports. Backups are encrypted before they leave the server.',
            ],
            'rights' => [
                'title' => 'Your choices',
                'body' => 'You can change your name and email address or delete your account in the settings. Contributions already published stay in the page history under their licence.',
            ],
        ],
    ],

    'not_advice' => [
        'title' => 'Not medical, legal or financial advice',
        'summary' => 'The atlas helps you think about work. It does not replace a doctor, a lawyer or an official.',
        'sections' => [
            'general' => [
                'title' => 'Information, not advice',
                'body' => 'Work Atlas gives general information about occupations. Make important decisions with people who know your situation.',
            ],
            'health' => [
                'title' => 'Health',
                'body' => 'Health sections show levels (low, medium, high) with reasons and sources, benefits as well as risks, by age group. They are reviewed by experts before they are shown. They never say anyone is unfit for work because of age, gender or any other trait. For your own health, see a doctor.',
            ],
            'legal_money' => [
                'title' => 'Licences, laws and money',
                'body' => 'Licences, legal requirements and income are shown only from sources, and income only as ranges. Rules and pay change; check with the responsible office before you rely on them.',
            ],
            'emergency' => [
                'title' => 'In an emergency',
                'body' => 'In an emergency in Bangladesh, call 999.',
            ],
        ],
    ],

    'neutrality' => [
        'title' => 'Neutrality and funding',
        'summary' => 'Who pays for Work Atlas, and why money never changes what the atlas says.',
        'sections' => [
            'nonprofit' => [
                'title' => 'Open and nonprofit',
                'body' => 'Work Atlas is an open, nonprofit project, paid for by donations and grants. It earns nothing from recommendations.',
            ],
            'neutral' => [
                'title' => 'Money does not change the data',
                'body' => 'Donors, sponsors and employers never change data or recommendations.',
            ],
            'sponsored' => [
                'title' => 'Sponsored content is labelled',
                'body' => 'If there is ever sponsored content, it is always clearly labelled as sponsored.',
            ],
            'accounts' => [
                'title' => 'Open accounts',
                'body' => 'Donations and costs will be published once the project\'s legal body is set up. No foreign funding is accepted before legal advice and the required registration.',
            ],
            'conflicts' => [
                'title' => 'Conflicts of interest',
                'body' => 'Contributors declare conflicts of interest, and edits that look like advertising are flagged for review.',
            ],
        ],
    ],
];
