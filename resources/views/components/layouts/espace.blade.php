<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.entete-html')
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-fond">
    <header class="border-b-[3px] border-ambre bg-marine">
        <div class="mx-auto flex max-w-2xl items-center justify-between px-5 py-3">
            <x-logo variante="inverse" :largeur="110" />

            <form method="POST" action="{{ route('deconnexion') }}">
                @csrf
                <button type="submit" class="min-h-11 rounded-lg px-3 text-base font-semibold text-white underline underline-offset-4 active:bg-white/10">
                    Se déconnecter
                </button>
            </form>
        </div>
    </header>

    <main class="mx-auto w-full max-w-2xl px-5 py-6">
        {{ $slot }}
    </main>
</body>
</html>