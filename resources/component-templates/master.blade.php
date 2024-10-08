<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>

    <x-dashed-core::frontend.head/>

    <meta charset="utf-8">
    <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
    />

    @stack('css')

    <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
    >

    <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
    >

    <link
            href="https://fonts.googleapis.com/css2?family=Lato:wght@200..800&display=swap"
            rel="stylesheet"
    >
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/app.css'])
    @vite(['resources/js/app.js'])

    @filamentStyles
    @stack('scripts')
</head>

<body class="font-sans antialiased min-h-dvh text-gray-800 bg-primary-100/10">
<x-dashed-core::frontend.body-extend/>

{{--<div--}}
{{--        x-data--}}
        {{--        x-intersect:enter="$store.top = true"--}}
        {{--        x-intersect:leave="$store.top = false"--}}
{{--></div>--}}

<x-navbar />
<x-blocks.breadcrumbs/>

<main class="grow">
    {{ $slot }}
</main>

<x-footer />
@livewire('notifications')
@filamentScripts
</body>
</html>
