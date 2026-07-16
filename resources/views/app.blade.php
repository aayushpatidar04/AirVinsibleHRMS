<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'HRMS') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        <style>
            .prose table,
[v-html] table {
    width: 100% !important;
    min-width: unset !important;
}

.prose col,
.prose colgroup,
[v-html] col,
[v-html] colgroup {
    display: none;
}

.prose td,
.prose th,
[v-html] td,
[v-html] th {
    min-width: unset !important;
    width: auto !important;
    word-break: break-word;
}
        </style>
    </head>
    <body class="font-sans antialiased bg-gray-100" style="font-family: 'Inter', sans-serif;">
        @inertia
    </body>
</html>