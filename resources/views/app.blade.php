<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Registra, sigue y resuelve las averías de mantenimiento de las tiendas.">
        <title>{{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <script src="/theme-init.js"></script>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
        <x-inertia::head />
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
