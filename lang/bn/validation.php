<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Bangla versions of Laravel's validation messages. ":attribute" is replaced by the field's
    | name from "attributes" below. Keep the keys in step with lang/en/validation.php.
    |
    */

    'accepted' => ':attribute মেনে নিতে হবে।',
    'accepted_if' => ':other যখন :value, তখন :attribute মেনে নিতে হবে।',
    'active_url' => ':attribute-এ একটি সঠিক URL দিন।',
    'after' => ':attribute-এ :date-এর পরের একটি তারিখ দিন।',
    'after_or_equal' => ':attribute-এ :date বা তার পরের একটি তারিখ দিন।',
    'alpha' => ':attribute-এ শুধু অক্ষর থাকতে পারবে।',
    'alpha_dash' => ':attribute-এ শুধু অক্ষর, সংখ্যা, ড্যাশ (-) আর আন্ডারস্কোর (_) থাকতে পারবে।',
    'alpha_num' => ':attribute-এ শুধু অক্ষর আর সংখ্যা থাকতে পারবে।',
    'any_of' => ':attribute সঠিক নয়।',
    'array' => ':attribute একটি তালিকা হতে হবে।',
    'array_keys' => ':attribute-এ শুধু এই key-গুলো থাকতে পারবে: :values।',
    'ascii' => ':attribute-এ শুধু ইংরেজি অক্ষর, সংখ্যা আর চিহ্ন থাকতে পারবে।',
    'base64' => ':attribute-এ সঠিক Base64 লেখা দিন।',
    'before' => ':attribute-এ :date-এর আগের একটি তারিখ দিন।',
    'before_or_equal' => ':attribute-এ :date বা তার আগের একটি তারিখ দিন।',
    'between' => [
        'array' => ':attribute-এ :min থেকে :max-টি জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার :min থেকে :max কিলোবাইটের মধ্যে হতে হবে।',
        'numeric' => ':attribute :min থেকে :max-এর মধ্যে হতে হবে।',
        'string' => ':attribute-এ :min থেকে :max অক্ষর থাকতে হবে।',
    ],
    'boolean' => ':attribute হ্যাঁ বা না হতে হবে।',
    'can' => ':attribute-এ এমন একটি মান আছে, যা দেওয়ার অনুমতি নেই।',
    'confirmed' => ':attribute দুইবার একইভাবে লেখা হয়নি।',
    'contains' => ':attribute-এ একটি দরকারি মান নেই।',
    'current_password' => 'পাসওয়ার্ডটি ঠিক নয়।',
    'date' => ':attribute-এ একটি সঠিক তারিখ দিন।',
    'date_equals' => ':attribute-এর তারিখ :date হতে হবে।',
    'date_format' => ':attribute এই ধরনে লিখুন: :format।',
    'decimal' => ':attribute-এ দশমিকের পরে :decimal ঘর থাকতে হবে।',
    'declined' => ':attribute প্রত্যাখ্যান করতে হবে।',
    'declined_if' => ':other যখন :value, তখন :attribute প্রত্যাখ্যান করতে হবে।',
    'different' => ':attribute আর :other আলাদা হতে হবে।',
    'digits' => ':attribute-এ ঠিক :digits অঙ্ক থাকতে হবে।',
    'digits_between' => ':attribute-এ :min থেকে :max অঙ্ক থাকতে হবে।',
    'dimensions' => ':attribute ছবির মাপ সঠিক নয়।',
    'distinct' => ':attribute-এ একই মান দুইবার আছে।',
    'doesnt_contain' => ':attribute-এ এগুলোর কোনোটি থাকতে পারবে না: :values।',
    'doesnt_end_with' => ':attribute এগুলোর কোনোটি দিয়ে শেষ হতে পারবে না: :values।',
    'doesnt_start_with' => ':attribute এগুলোর কোনোটি দিয়ে শুরু হতে পারবে না: :values।',
    'email' => ':attribute-এ একটি সঠিক ইমেইল ঠিকানা দিন।',
    'encoding' => ':attribute :encoding এনকোডিংয়ে হতে হবে।',
    'ends_with' => ':attribute এগুলোর যেকোনো একটি দিয়ে শেষ হতে হবে: :values।',
    'enum' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'exists' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'extensions' => ':attribute ফাইলের ধরন এগুলোর একটি হতে হবে: :values।',
    'file' => ':attribute একটি ফাইল হতে হবে।',
    'filled' => ':attribute খালি রাখা যাবে না।',
    'gt' => [
        'array' => ':attribute-এ :value-টির বেশি জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার :value কিলোবাইটের বেশি হতে হবে।',
        'numeric' => ':attribute :value-এর বেশি হতে হবে।',
        'string' => ':attribute-এ :value অক্ষরের বেশি থাকতে হবে।',
    ],
    'gte' => [
        'array' => ':attribute-এ অন্তত :value-টি জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার অন্তত :value কিলোবাইট হতে হবে।',
        'numeric' => ':attribute অন্তত :value হতে হবে।',
        'string' => ':attribute-এ অন্তত :value অক্ষর থাকতে হবে।',
    ],
    'hex_color' => ':attribute-এ একটি সঠিক হেক্স রং দিন।',
    'image' => ':attribute একটি ছবি হতে হবে।',
    'in' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'in_array' => ':attribute :other-এর মধ্যে থাকতে হবে।',
    'in_array_keys' => ':attribute-এ এই key-গুলোর অন্তত একটি থাকতে হবে: :values।',
    'integer' => ':attribute একটি পূর্ণসংখ্যা হতে হবে।',
    'ip' => ':attribute-এ একটি সঠিক IP ঠিকানা দিন।',
    'ipv4' => ':attribute-এ একটি সঠিক IPv4 ঠিকানা দিন।',
    'ipv6' => ':attribute-এ একটি সঠিক IPv6 ঠিকানা দিন।',
    'json' => ':attribute-এ সঠিক JSON লেখা দিন।',
    'list' => ':attribute একটি তালিকা হতে হবে।',
    'lowercase' => ':attribute ছোট হাতের অক্ষরে লিখতে হবে।',
    'lt' => [
        'array' => ':attribute-এ :value-টির কম জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার :value কিলোবাইটের কম হতে হবে।',
        'numeric' => ':attribute :value-এর কম হতে হবে।',
        'string' => ':attribute-এ :value অক্ষরের কম থাকতে হবে।',
    ],
    'lte' => [
        'array' => ':attribute-এ :value-টির বেশি জিনিস থাকতে পারবে না।',
        'file' => ':attribute ফাইলের আকার :value কিলোবাইটের বেশি হতে পারবে না।',
        'numeric' => ':attribute :value-এর বেশি হতে পারবে না।',
        'string' => ':attribute-এ :value অক্ষরের বেশি থাকতে পারবে না।',
    ],
    'mac_address' => ':attribute-এ একটি সঠিক MAC ঠিকানা দিন।',
    'max' => [
        'array' => ':attribute-এ :max-টির বেশি জিনিস থাকতে পারবে না।',
        'file' => ':attribute ফাইলের আকার :max কিলোবাইটের বেশি হতে পারবে না।',
        'numeric' => ':attribute :max-এর বেশি হতে পারবে না।',
        'string' => ':attribute-এ :max অক্ষরের বেশি থাকতে পারবে না।',
    ],
    'max_digits' => ':attribute-এ :max অঙ্কের বেশি থাকতে পারবে না।',
    'mimes' => ':attribute ফাইলের ধরন এগুলোর একটি হতে হবে: :values।',
    'mimetypes' => ':attribute ফাইলের ধরন এগুলোর একটি হতে হবে: :values।',
    'min' => [
        'array' => ':attribute-এ অন্তত :min-টি জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার অন্তত :min কিলোবাইট হতে হবে।',
        'numeric' => ':attribute অন্তত :min হতে হবে।',
        'string' => ':attribute-এ অন্তত :min অক্ষর থাকতে হবে।',
    ],
    'min_digits' => ':attribute-এ অন্তত :min অঙ্ক থাকতে হবে।',
    'missing' => ':attribute থাকা চলবে না।',
    'missing_if' => ':other যখন :value, তখন :attribute থাকা চলবে না।',
    'missing_unless' => ':other যদি :value না হয়, তাহলে :attribute থাকা চলবে না।',
    'missing_with' => ':values থাকলে :attribute থাকা চলবে না।',
    'missing_with_all' => ':values সবগুলো থাকলে :attribute থাকা চলবে না।',
    'multiple_of' => ':attribute :value-এর গুণিতক হতে হবে।',
    'not_in' => 'বেছে নেওয়া :attribute সঠিক নয়।',
    'not_regex' => ':attribute-এর ধরন সঠিক নয়।',
    'numeric' => ':attribute একটি সংখ্যা হতে হবে।',
    'password' => [
        'letters' => ':attribute-এ অন্তত একটি অক্ষর থাকতে হবে।',
        'mixed' => ':attribute-এ অন্তত একটি বড় হাতের আর একটি ছোট হাতের ইংরেজি অক্ষর থাকতে হবে।',
        'numbers' => ':attribute-এ অন্তত একটি সংখ্যা থাকতে হবে।',
        'symbols' => ':attribute-এ অন্তত একটি চিহ্ন (যেমন ! বা #) থাকতে হবে।',
        'uncompromised' => 'এই :attribute আগে কোনো তথ্য ফাঁসের ঘটনায় প্রকাশ পেয়েছে। অন্য একটি :attribute বেছে নিন।',
    ],
    'present' => ':attribute থাকতে হবে।',
    'present_if' => ':other যখন :value, তখন :attribute থাকতে হবে।',
    'present_unless' => ':other যদি :value না হয়, তাহলে :attribute থাকতে হবে।',
    'present_with' => ':values থাকলে :attribute-ও থাকতে হবে।',
    'present_with_all' => ':values সবগুলো থাকলে :attribute-ও থাকতে হবে।',
    'prohibited' => ':attribute দেওয়া যাবে না।',
    'prohibited_if' => ':other যখন :value, তখন :attribute দেওয়া যাবে না।',
    'prohibited_if_accepted' => ':other মেনে নিলে :attribute দেওয়া যাবে না।',
    'prohibited_if_declined' => ':other প্রত্যাখ্যান করলে :attribute দেওয়া যাবে না।',
    'prohibited_unless' => ':other যদি :values-এর মধ্যে না থাকে, তাহলে :attribute দেওয়া যাবে না।',
    'prohibits' => ':attribute থাকলে :other দেওয়া যাবে না।',
    'regex' => ':attribute-এর ধরন সঠিক নয়।',
    'required' => ':attribute দিতে হবে।',
    'required_array_keys' => ':attribute-এ এগুলোর জন্য মান থাকতে হবে: :values।',
    'required_if' => ':other যখন :value, তখন :attribute দিতে হবে।',
    'required_if_accepted' => ':other মেনে নিলে :attribute দিতে হবে।',
    'required_if_declined' => ':other প্রত্যাখ্যান করলে :attribute দিতে হবে।',
    'required_unless' => ':other যদি :values-এর মধ্যে না থাকে, তাহলে :attribute দিতে হবে।',
    'required_with' => ':values থাকলে :attribute-ও দিতে হবে।',
    'required_with_all' => ':values সবগুলো থাকলে :attribute-ও দিতে হবে।',
    'required_without' => ':values না থাকলে :attribute দিতে হবে।',
    'required_without_all' => ':values-এর কোনোটিই না থাকলে :attribute দিতে হবে।',
    'same' => ':attribute আর :other একই হতে হবে।',
    'size' => [
        'array' => ':attribute-এ ঠিক :size-টি জিনিস থাকতে হবে।',
        'file' => ':attribute ফাইলের আকার ঠিক :size কিলোবাইট হতে হবে।',
        'numeric' => ':attribute ঠিক :size হতে হবে।',
        'string' => ':attribute-এ ঠিক :size অক্ষর থাকতে হবে।',
    ],
    'starts_with' => ':attribute এগুলোর যেকোনো একটি দিয়ে শুরু হতে হবে: :values।',
    'string' => ':attribute লেখা হতে হবে।',
    'timezone' => ':attribute-এ একটি সঠিক টাইমজোন দিন।',
    'unique' => 'এই :attribute আগেই ব্যবহার করা হয়েছে।',
    'uploaded' => ':attribute আপলোড করা যায়নি।',
    'uppercase' => ':attribute বড় হাতের অক্ষরে লিখতে হবে।',
    'url' => ':attribute-এ একটি সঠিক URL দিন।',
    'ulid' => ':attribute-এ একটি সঠিক ULID দিন।',
    'uuid' => ':attribute-এ একটি সঠিক UUID দিন।',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Field names as people read them in messages, instead of the form field's key.
    |
    */

    'attributes' => [
        'name' => 'নাম',
        'email' => 'ইমেইল',
        'password' => 'পাসওয়ার্ড',
        'current_password' => 'এখনকার পাসওয়ার্ড',
        'password_confirmation' => 'পাসওয়ার্ড নিশ্চিতকরণ',
        'code' => 'কোড',
        'recovery_code' => 'রিকভারি কোড',
        'locale' => 'ভাষা',
    ],

];
