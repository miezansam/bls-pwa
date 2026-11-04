@php
    $estimation = $this->estimation;
    $champ = 'w-full rounded-lg border-2 border-trait bg-white px-4 py-3 text-lg focus:border-encre';
    $centres = $this->zones
        ->mapWithKeys(fn($zone) => [$zone->id => [$zone->centre_latitude, $zone->centre_longitude]])
        ->all();
@endphp

<div>
    <h1 class="font-display text-4xl font-bold">Nouvelle commande</h1>

    <form wire:submit="enregistrer" class="mt-6 flex flex-col gap-8 ">
        <fieldset>
            <legend class="mb-2 font-semibold">Que voulez-vous envoyer ?</legend>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['colis' => 'Un colis', 'repas' => 'Un repas'] as $valeur => $libelle)
                    <label
                        class="flex min-h-14 cursor-pointer items-center justify-center rounded-lg border-2 border-marine px-4 text-lg font-semibold has-checked:bg-marine has-checked:text-white has-focus-visible:outline-3">
                        <input type="radio" wire:model.live="type" value="{{ $valeur }}" class="sr-only">
                        {{ $libelle }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        @foreach ([['retrait', 'Où récupérer ?'], ['livraison', 'Où livrer ?']] as [$lieu, $titre])
            <section class="flex flex-col gap-4">
                <h2 class="font-display text-2xl font-bold">{{ $titre }}</h2>

                <div>
                    <label for="{{ $lieu }}-zone" class="mb-1 block font-semibold">Quartier ou village</label>
                    <select id="{{ $lieu }}-zone" wire:model.live="{{ $lieu }}ZoneId"
                        class="{{ $champ }}">
                        <option value="">Choisir…</option>
                        @foreach ($this->zones as $zone)
                            <option value="{{ $zone->id }}">
                                {{ $zone->nom }}@if ($lieu === 'livraison')
                                    ({{ number_format($zone->frais_livraison, 0, ',', ' ') }} FCFA)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error($lieu . 'ZoneId')
                        <p class="mt-1 text-base text-alerte" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.carte-epingle :lieu="$lieu" :centres="$centres" />

                <div>
                    <label for="{{ $lieu }}-repere" class="mb-1 block font-semibold">Repère</label>
                    <input id="{{ $lieu }}-repere" type="text" wire:model="{{ $lieu }}Repere"
                        class="{{ $champ }}" placeholder="Ex : derrière le marché, portail bleu">
                    @error($lieu . 'Repere')
                        <p class="mt-1 text-base text-alerte" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="{{ $lieu }}-tel" class="mb-1 block font-semibold">Numéro à appeler</label>
                    <input id="{{ $lieu }}-tel" type="tel" inputmode="tel" autocomplete="tel"
                        wire:model="{{ $lieu }}Telephone" class="{{ $champ }}"
                        placeholder="07 00 00 00 00">
                    @error($lieu . 'Telephone')
                        <p class="mt-1 text-base text-alerte" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </section>
        @endforeach

        <section class="flex flex-col gap-4">
            <div>
                <label for="description" class="mb-1 block font-semibold">
                    {{ $type === 'repas' ? 'Que voulez-vous commander ?' : 'Que doit-on livrer ?' }}
                </label>
                <textarea id="description" rows="3" wire:model="description" class="{{ $champ }}"></textarea>
                @error('description')
                    <p class="mt-1 text-base text-alerte" role="alert">{{ $message }}</p>
                @enderror
            </div>

            @if ($type === 'repas')
                <div>
                    <label for="nombre-repas" class="mb-1 block font-semibold">Nombre de repas</label>
                    <input id="nombre-repas" type="number" inputmode="numeric" min="1" max="20"
                        wire:model.live.debounce.500ms="nombreRepas" class="{{ $champ }}">
                    @error('nombreRepas')
                        <p class="mt-1 text-base text-alerte" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <fieldset class="flex flex-col gap-3">
                <legend class="mb-1 font-semibold">Options</legend>
                @foreach (['express' => 'Express', 'doubleCourse' => 'Double course', 'speciale' => 'Commande spéciale'] as $propriete => $libelle)
                    <label class="flex min-h-11 items-center gap-3 text-lg">
                        <input type="checkbox" wire:model.live="{{ $propriete }}" class="size-6 accent-marine">
                        {{ $libelle }}
                    </label>
                @endforeach
            </fieldset>
        </section>

        <p class="text-lg">Paiement : <strong>espèces à la livraison</strong></p>

        <div class="border-y-2 border-marine py-4" aria-live="polite" wire:loading.class="opacity-50"
            wire:target="type,retraitZoneId,livraisonZoneId,express,doubleCourse,speciale,nombreRepas">
            @if ($estimation['prix'])
                <p class="text-gris">Prix estimé de la livraison</p>
                <p class="whitespace-nowrap font-display text-4xl font-bold">
                    {{ number_format($estimation['prix']->fraisLivraison, 0, ',', ' ') }} FCFA
                </p>
                @if ($type === 'repas')
                    <p class="mt-1 text-base text-gris">Les repas sont avancés par le livreur, à lui rembourser en plus.
                    </p>
                @endif
            @elseif ($estimation['erreur'])
                <p class="text-alerte" role="alert">{{ $estimation['erreur'] }}</p>
            @else
                <p class="text-gris">Choisissez les deux zones pour voir le prix.</p>
            @endif
        </div>

        <x-ui.barre-action class="-mx-5 -mb-6 lg:mx-0 lg:mb-0">
            <x-ui.bouton cible="enregistrer">Commander</x-ui.bouton>
        </x-ui.barre-action>
    </form>
</div>
