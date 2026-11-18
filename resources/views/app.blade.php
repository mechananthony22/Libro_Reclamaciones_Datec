<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="author" content="Datec Technologies">
        <meta name="theme-color" content="#2563EB">

        <link rel="icon" href="/faviconDLR.ico" sizes="any">
        <link rel="icon" href="/faviconDLR.svg" type="image/svg+xml">
        <link rel="icon" href="/faviconDLR.png" type="image/png">
        <link rel="apple-touch-icon" href="/faviconDLR.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Libro de Reclamaciones Digital - Datec') }}</title>
            <meta name="description" content="Plataforma SaaS Multiempresa del Libro de Reclamaciones Digital conforme a la normativa legal de INDECOPI en Perú.">
            <meta property="og:site_name" content="Libro de Reclamaciones Digital Datec">
            <meta property="og:type" content="website">
            <meta name="twitter:card" content="summary_large_image">
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
