<?php

return [
    'title' => 'Open data API',
    'intro' => 'Read Work Atlas data from your own app or research. No key needed. The API only reads; it never changes data.',
    'base_url' => 'Address',
    'spec' => 'OpenAPI description (JSON)',
    'endpoints' => 'Endpoints',
    'parameters' => 'Parameters',
    'required' => 'required',
    'no_parameters' => 'No parameters.',
    'rules_title' => 'Before you use the data',
    'rules' => [
        'license' => 'The data is :license. Credit ":attribution" and share what you build on it under the same license.',
        'status' => 'Every value has a status. "AI draft" values are unchecked drafts; show the status next to the value.',
        'sensitive' => 'Income, health and legal values appear only once they are verified.',
        'advice' => 'Nothing here is medical, legal or financial advice, and no value is a promise of work or income.',
        'limit' => 'At most :count requests per minute from one address. Please cache what you read.',
    ],
    'operations' => [
        'listOccupations' => 'The public occupations, a page at a time; filter by ISCO group.',
        'getOccupation' => 'One occupation with its values, their status and sources.',
        'listFields' => 'What each field means: labels, options and scale anchors.',
        'searchOccupations' => 'Find occupations by any name, local and regional names included.',
        'getOpenApi' => 'This API described in OpenAPI 3.1.',
    ],
];
