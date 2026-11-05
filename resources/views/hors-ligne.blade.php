<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ config('bls.couleurs.marine') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>Pas de connexion - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh bg-white">
    <header class="border-b-[3px] border-ambre bg-marine px-5 py-4">
        <x-logo variante="inverse" :largeur="130" />
    </header>

    <main class="mx-auto flex w-full max-w-md flex-col gap-6 px-5 pt-8">
        <div>
            <h1 class="font-display text-4xl font-bold leading-none">Pas de connexion</h1>
            <p class="mt-3 text-lg text-gris">Le réseau ne répond pas. Vérifiez votre connexion, puis réessayez.</p>
        </div>

        <x-ui.bouton type="button" onclick="location.reload()">Réessayer</x-ui.bouton>
    </main>
</body>
</html>