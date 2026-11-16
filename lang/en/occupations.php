<?php

// The public occupation pages (PLAN M13). Keep keys in step with lang/bn/occupations.php.
return [
    'index' => [
        'title' => 'Occupations',
        'intro' => 'Every kind of work in Bangladesh, with what it takes and what it is like. Most of it is still an AI draft that people are checking.',
        'groups_heading' => 'Browse by group',
        'all_heading' => 'All occupations',
        'count' => ':count occupations',
        'empty' => 'No occupations are listed yet.',
        'previous' => 'Previous page',
        'next' => 'Next page',
        'page' => 'Page :page of :last',
        'pagination' => 'Pages',
    ],

    'group' => [
        'code' => 'ISCO-08 group :code',
        'children_heading' => 'Groups in this group',
        'occupations_heading' => 'Occupations in this group',
        'empty' => 'No occupations are listed in this group yet.',
        'all' => 'All groups',
    ],

    'show' => [
        'isco' => 'ISCO-08 unit group :code',
        'names_heading' => 'Names',
        'draft_notice' => 'Some of this page is not verified yet. Values marked "AI draft" were written by AI and are waiting for people to check them.',
        'medical_notice' => 'This is general information, not medical advice. Ask a doctor about your own health.',
        'withheld' => 'Not verified yet. This is shown only after a reviewer has checked it.',
        'not_filled' => 'Not filled in yet.',
        'unknown' => 'Not known yet.',
        'yes' => 'Yes',
        'no' => 'No',
        'scale' => ':point of 5',
        'status' => 'Status',
        'sources_heading' => 'Sources',
        'no_sources' => 'No sources are cited on this page yet.',
        'source_ref' => 'Source :number',
        'accessed' => 'accessed :date',
        'archived' => 'archived copy',
        'report_heading' => 'Found a mistake?',
        'report_body' => 'Tell us what is wrong on this page. You do not need an account.',
        'report_button' => 'Report a mistake',
        'discuss' => 'Discuss',
        'compare_button' => 'Compare with other occupations',
        'talk_button' => 'Talk page (:count discussions)',
        'attribution' => 'Written by Work Atlas contributors. AI drafts are checked by people before they are trusted.',
        'license' => 'You may reuse this page under CC BY-SA 4.0.',
        'changed_at' => 'Last changed :date',
    ],

    'status' => [
        'ai_draft' => 'AI draft',
        'sourced' => 'Has a source',
        'community_supported' => 'Supported by people',
        'verified' => 'Verified',
        'disputed' => 'Disputed',
    ],

    'name_types' => [
        'primary' => 'Main name',
        'alias' => 'Also called',
        'local' => 'Local name',
        'regional' => 'Regional name',
    ],

    'groups' => [
        'names' => 'Names',
        'entry' => 'Getting started',
        'workload' => 'Workload',
        'conditions' => 'Working conditions',
        'health' => 'Health',
    ],
];
