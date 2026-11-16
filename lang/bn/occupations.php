<?php

// পেশার public পাতা (PLAN M13)। lang/en/occupations.php-এর সাথে key মিলিয়ে রাখুন।
return [
    'index' => [
        'title' => 'পেশা',
        'intro' => 'বাংলাদেশের সব ধরনের কাজ: কী লাগে, কাজটা কেমন। বেশিরভাগ তথ্য এখনও AI-এর খসড়া, মানুষ তা যাচাই করছেন।',
        'groups_heading' => 'দল ধরে খুঁজুন',
        'all_heading' => 'সব পেশা',
        'count' => ':countটি পেশা',
        'empty' => 'এখনও কোনো পেশা তালিকায় নেই।',
        'previous' => 'আগের পাতা',
        'next' => 'পরের পাতা',
        'page' => ':last পাতার :page নম্বর',
        'pagination' => 'পাতা',
    ],

    'group' => [
        'code' => 'ISCO-08 দল :code',
        'children_heading' => 'এই দলের ভেতরের দল',
        'occupations_heading' => 'এই দলের পেশা',
        'empty' => 'এই দলে এখনও কোনো পেশা তালিকায় নেই।',
        'all' => 'সব দল',
    ],

    'show' => [
        'isco' => 'ISCO-08 ইউনিট দল :code',
        'names_heading' => 'নাম',
        'draft_notice' => 'এই পাতার কিছু তথ্য এখনও যাচাই হয়নি। "AI খসড়া" লেখা তথ্য AI লিখেছে, মানুষ এখনও তা দেখেননি।',
        'medical_notice' => 'এটা সাধারণ তথ্য, চিকিৎসা পরামর্শ নয়। নিজের স্বাস্থ্যের ব্যাপারে ডাক্তারের সাথে কথা বলুন।',
        'withheld' => 'এখনও যাচাই হয়নি। একজন reviewer দেখার পরেই এটা দেখানো হবে।',
        'not_filled' => 'এখনও লেখা হয়নি।',
        'unknown' => 'এখনও জানা নেই।',
        'yes' => 'হ্যাঁ',
        'no' => 'না',
        'scale' => '৫-এর মধ্যে :point',
        'status' => 'অবস্থা',
        'sources_heading' => 'সূত্র',
        'no_sources' => 'এই পাতায় এখনও কোনো সূত্র নেই।',
        'source_ref' => 'সূত্র :number',
        'accessed' => ':date তারিখে দেখা',
        'archived' => 'সংরক্ষিত কপি',
        'report_heading' => 'ভুল চোখে পড়েছে?',
        'report_body' => 'এই পাতায় কী ভুল আছে জানান। অ্যাকাউন্ট লাগবে না।',
        'report_button' => 'ভুল জানান',
        'discuss' => 'আলোচনা',
        'compare_button' => 'অন্য পেশার সাথে তুলনা করুন',
        'talk_button' => 'আলোচনা পাতা (:countটি আলোচনা)',
        'attribution' => 'লিখেছেন Work Atlas-এর অবদানকারীরা। AI-এর খসড়া বিশ্বাস করার আগে মানুষ যাচাই করেন।',
        'license' => 'এই পাতা CC BY-SA 4.0 লাইসেন্সে আবার ব্যবহার করা যায়।',
        'changed_at' => 'শেষ বদল :date',
    ],

    'status' => [
        'ai_draft' => 'AI খসড়া',
        'sourced' => 'সূত্র আছে',
        'community_supported' => 'মানুষের সমর্থন আছে',
        'verified' => 'যাচাই করা',
        'disputed' => 'বিতর্কিত',
    ],

    'name_types' => [
        'primary' => 'মূল নাম',
        'alias' => 'অন্য নাম',
        'local' => 'স্থানীয় নাম',
        'regional' => 'আঞ্চলিক নাম',
    ],

    'groups' => [
        'names' => 'নাম',
        'entry' => 'শুরু করতে যা লাগে',
        'workload' => 'কাজের চাপ',
        'conditions' => 'কাজের পরিবেশ',
        'health' => 'স্বাস্থ্য',
    ],
];
