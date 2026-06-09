<?php

return [
    // Block-types die de Content Studio nooit mag genereren.
    'excluded_blocks' => [
        'globalBlock',
    ],

    // Handmatige veld-override per block-type. Vorm gelijk aan FieldDescriptor::toArray().
    // 'blocktype' => [ ['name' => 'title', 'type' => 'text', 'label' => 'Titel'] ],
    'overrides' => [],
];
