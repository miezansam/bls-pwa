<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#15803d">
    <title>{{ $title ?? 'BLS Livraison' }}</title>
    <style>[x-cloak] { display: none !important; }</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-green-50 text-gray-900 antialiased">
    <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-5 py-8">
        <div class="mb-8 text-center">
            <p class="text-3xl font-extrabold tracking-tight text-green-800">BLS Livraison</p>
            <p class="mt-1 text-base text-gray-600">Colis et repas à Adzopé</p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-md">
            {{ $slot }}
        </div>
    </main>
</body>
</html>