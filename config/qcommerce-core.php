<?php

use Illuminate\Support\Str;

return [

    'path' => 'qcommerce',

    'sites' => [
        0 => [
            'id' => 'nxe',
            'name' => 'NXE',
            'locales' => ['en', 'nl']
        ],
        1 => [
            'id' => 'gamekoning',
            'name' => 'Gamekoning',
            'locales' => ['en', 'nl']
        ]
    ],

    'forms' => [
        'contact' => [
            'send_to_field' => 'email',
            'honeypot_field_name' => 'weird_input',
            'fields' => [
                'first_name' => [
                    'rules' => [
                        'max:255',
                    ]
                ],
                'last_name' => [
                    'rules' => [
                        'required',
                        'max:255',
                    ]
                ],
                'email' => [
                    'rules' => [
                        'required',
                        'email:rfc',
                        'max:255',
                    ]
                ],
                'phone_number' => [
                    'rules' => [
                        'required',
                        'max:255',
                    ]
                ],
                'subject' => [
                    'rules' => [
                        'max:255',
                    ]
                ],
                'message' => [
                    'rules' => [
                        'required',
                        'max:1500',
                    ]
                ]
            ]
        ]
    ],

    'blocks' => [
        "header" => [
            "display" => "Header",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Header titel",
                    "required" => true
                ],
                [
                    "handle" => "subtitle",
                    "inputType" => "text",
                    "placeholder" => "Header subtitel"
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Achtergrond afbeelding (url)"
                ],
            ]
        ],
        "info-with-image-left" => [
            "display" => "Info with image left",
            "fields" => [
                [
                    "handle" => "subtitle",
                    "inputType" => "text",
                    "placeholder" => "Header subtitel"
                ],
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Header titel",
                    "required" => true
                ],
                [
                    "handle" => "content",
                    "inputType" => "editor",
                    "placeholder" => "Content"
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding"
                ],
            ]
        ],
        "advertising-block" => [
            "display" => "Advertising block",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Header titel",
                    "required" => true
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding"
                ],
            ]
        ],
        "advertising-block-with-text-right" => [
            "display" => "Advertising block with text right",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Header titel",
                    "required" => true
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding"
                ],
            ]
        ],
        "recommended-products" => [
            "display" => "Recommended products",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Title",
                    "required" => true
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
            ]
        ],
        "showcase-header" => [
            "display" => "Showcase header",
            "fields" => [
                [
                    "handle" => "subtitle",
                    "inputType" => "text",
                    "placeholder" => "Header subtitel"
                ],
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Header titel",
                    "required" => true
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
                [
                    "handle" => "background-image",
                    "inputType" => "text",
                    "placeholder" => "Achtergrond afbeelding"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding"
                ],
                [
                    "handle" => "image-2",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding 2"
                ],
                [
                    "handle" => "image-3",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding 3"
                ],
                [
                    "handle" => "image-4",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding 4"
                ],
                [
                    "handle" => "image-5",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding 5"
                ],
            ]
        ],
        "4-column-info-blocks" => [
            "display" => "4 column info blocks",
            "fields" => [
                [
                    "handle" => "title-1",
                    "inputType" => "text",
                    "placeholder" => "Titel 1",
                    "required" => true
                ],
                [
                    "handle" => "subtitle-1",
                    "inputType" => "text",
                    "placeholder" => "Subtitel 1"
                ],
                [
                    "handle" => "link-1",
                    "inputType" => "text",
                    "placeholder" => "Link"
                ],
                [
                    "handle" => "title-2",
                    "inputType" => "text",
                    "placeholder" => "Titel 2",
                    "required" => true
                ],
                [
                    "handle" => "subtitle-2",
                    "inputType" => "text",
                    "placeholder" => "Subtitel 2"
                ],
                [
                    "handle" => "link-2",
                    "inputType" => "text",
                    "placeholder" => "Link"
                ],
                [
                    "handle" => "title-3",
                    "inputType" => "text",
                    "placeholder" => "Titel 3",
                    "required" => true
                ],
                [
                    "handle" => "subtitle-3",
                    "inputType" => "text",
                    "placeholder" => "Subtitel 3"
                ],
                [
                    "handle" => "link-3",
                    "inputType" => "text",
                    "placeholder" => "Link"
                ],
                [
                    "handle" => "title-4",
                    "inputType" => "text",
                    "placeholder" => "Titel 4",
                    "required" => true
                ],
                [
                    "handle" => "subtitle-4",
                    "inputType" => "text",
                    "placeholder" => "Subtitel 4"
                ],
                [
                    "handle" => "link-4",
                    "inputType" => "text",
                    "placeholder" => "Link"
                ],
            ]
        ],
        "newsletter-block" => [
            "display" => "Newsletter block",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Title",
                    "required" => true
                ],
                [
                    "handle" => "sub_title",
                    "inputType" => "text",
                    "placeholder" => "Subtitle"
                ],
                [
                    "handle" => "image",
                    "inputType" => "text",
                    "placeholder" => "Afbeelding"
                ],
            ]
        ],
        "latest-blogs" => [
            "display" => "Latest blogs",
            "fields" => [
                [
                    "handle" => "handle",
                    "inputType" => "text",
                    "placeholder" => "Handle"
                ],
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Title",
                    "required" => true
                ],
                [
                    "handle" => "sub_title",
                    "inputType" => "text",
                    "placeholder" => "Subtitle"
                ],
                [
                    "handle" => "button_text",
                    "inputType" => "text",
                    "placeholder" => "Button tekst"
                ],
                [
                    "handle" => "button_url",
                    "inputType" => "text",
                    "placeholder" => "Button URL"
                ],
            ]
        ],
        "all-categories" => [
            "display" => "Alle toplevel categorieeen display",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Titel",
                    "required" => true
                ],
                [
                    "handle" => "subtitle",
                    "inputType" => "textarea",
                    "placeholder" => "Subtitel"
                ],
            ]
        ],
        "faq-block" => [
            "display" => "FAQ blok",
            "fields" => []
        ],
        "contact-block" => [
            "display" => "Contact blok",
            "fields" => [
                [
                    "handle" => "title",
                    "inputType" => "text",
                    "placeholder" => "Titel"
                ],
            ]
        ],
//        "products" => [
//            "display" => "4 producten display",
//            "fields" => [
//                [
//                    "handle" => "title",
//                    "inputType" => "text",
//                    "placeholder" => "Titel",
//                    "required" => true
//                ],
//                [
//                    "handle" => "subtitle",
//                    "inputType" => "textarea",
//                    "placeholder" => "Subtitel"
//                ],
//            ]
//        ],
//        "populair-products" => [
//            "display" => "4 populaire producten display",
//            "fields" => [
//                [
//                    "handle" => "title",
//                    "inputType" => "text",
//                    "placeholder" => "Titel",
//                    "required" => true
//                ],
//                [
//                    "handle" => "subtitle",
//                    "inputType" => "textarea",
//                    "placeholder" => "Subtitel"
//                ],
//                [
//                    "handle" => "button_text",
//                    "inputType" => "text",
//                    "placeholder" => "Button tekst"
//                ],
//                [
//                    "handle" => "button_url",
//                    "inputType" => "text",
//                    "placeholder" => "Button URL"
//                ],
//            ]
//        ],
//        "newest-products" => [
//            "display" => "4 nieuwste producten display",
//            "fields" => [
//                [
//                    "handle" => "title",
//                    "inputType" => "text",
//                    "placeholder" => "Titel",
//                    "required" => true
//                ],
//                [
//                    "handle" => "subtitle",
//                    "inputType" => "textarea",
//                    "placeholder" => "Subtitel"
//                ],
//                [
//                    "handle" => "button_text",
//                    "inputType" => "text",
//                    "placeholder" => "Button tekst"
//                ],
//                [
//                    "handle" => "button_url",
//                    "inputType" => "text",
//                    "placeholder" => "Button URL"
//                ],
//            ]
//        ],
//        "all-products" => [
//            "display" => "All products display",
//            "fields" => [
//
//            ]
//        ],
//        "contact-form" => [
//            "display" => "Contact formulier",
//            "fields" => [
//                [
//                    "handle" => "title",
//                    "inputType" => "text",
//                    "placeholder" => "Titel"
//                ],
//                [
//                    "handle" => "last_widget_title",
//                    "inputType" => "text",
//                    "placeholder" => "Laatste widget titel onder formulier"
//                ],
//                [
//                    "handle" => "last_widget_content",
//                    "inputType" => "textarea",
//                    "placeholder" => "Laatste widget content onder formulier"
//                ],
//            ]
//        ],
//        "content-block" => [
//            "display" => "Content block",
//            "fields" => [
//                [
//                    "handle" => "title",
//                    "inputType" => "text",
//                    "placeholder" => "Titel",
//                    "required" => false
//                ],
//                [
//                    "handle" => "content",
//                    "inputType" => "textarea",
//                    "placeholder" => "Content",
//                    "required" => false
//                ]
//            ]
//        ],
//        "content-image-block" => [
//            "display" => "Content image block",
//            "fields" => [
//                [
//                    "handle" => "image",
//                    "inputType" => "text",
//                    "placeholder" => "Afbeelding",
//                    "required" => true
//                ]
//            ]
//        ],
//        "faq-block" => [
//            "display" => "FAQ block",
//            "fields" => [
//            ]
//        ],
    ],

    'currentSite' => env('QCOMMERCE_SITE_ID', 'nxe')
];
