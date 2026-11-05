<?php

declare(strict_types=1);

namespace App\Livewire\Client;

use App\Actions\CreerCommandeAction;
use App\Data\OptionsCommande;
use App\Enums\ModePaiement;
use App\Enums\TypeCommande;
use App\Exceptions\LivraisonNonDisponibleException;
use App\Exceptions\TarifIntrouvableException;
use App\Models\Zone;
use App\Services\CalculerPrixService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.espace')]
#[Title('Nouvelle commande')]
class NouvelleCommande extends Component
{
    public string $type = 'colis';

    public string $retraitZoneId = '';

    public string $retraitRepere = '';

    public string $retraitTelephone = '';

    public string $livraisonZoneId = '';

    public string $livraisonRepere = '';

    public string $livraisonTelephone = '';

    public string $description = '';

    public bool $express = false;

    public bool $doubleCourse = false;

    public bool $speciale = false;

    public string $nombreRepas = '1';

    public ?float $retraitLatitude = null;

    public ?float $retraitLongitude = null;

    public ?float $livraisonLatitude = null;

    public ?float $livraisonLongitude = null;

    public function mount(): void
    {
        $this->retraitTelephone = (string) auth()->user()->telephone;
    }

    /** @return Collection<int, Zone> */
    #[Computed]
    public function zones(): Collection
    {
        return Zone::query()
            ->where('est_active', true)
            ->whereNotNull('frais_livraison')
            ->orderBy('frais_livraison')
            ->orderBy('nom')
            ->get();
    }

    /** @return array{prix: ?\App\Data\EstimationPrix, erreur: ?string} */
    #[Computed]
    public function estimation(): array
    {
        $retrait = $this->zone($this->retraitZoneId);
        $livraison = $this->zone($this->livraisonZoneId);

        if ($retrait === null || $livraison === null) {
            return ['prix' => null, 'erreur' => null];
        }

        [$retraitLat, $retraitLng] = $this->position($retrait, $this->retraitLatitude, $this->retraitLongitude);
        [$livraisonLat, $livraisonLng] = $this->position($livraison, $this->livraisonLatitude, $this->livraisonLongitude);

        try {
            $prix = app(CalculerPrixService::class)->estimer(
                type: TypeCommande::tryFrom($this->type) ?? TypeCommande::Colis,
                zoneRetraitId: $retrait->id,
                zoneLivraisonId: $livraison->id,
                retraitLatitude: $retraitLat,
                retraitLongitude: $retraitLng,
                livraisonLatitude: $livraisonLat,
                livraisonLongitude: $livraisonLng,
                options: $this->options(),
            );
        } catch (LivraisonNonDisponibleException | TarifIntrouvableException | DomainException $e) {
            return ['prix' => null, 'erreur' => $e->getMessage()];
        }

        return ['prix' => $prix, 'erreur' => null];
    }

    public function enregistrer(CreerCommandeAction $creer): void
    {
        $this->validate();

        $retrait = $this->zone($this->retraitZoneId);
        $livraison = $this->zone($this->livraisonZoneId);

        if ($retrait === null || $livraison === null) {
            $this->addError('livraisonZoneId', 'Choisissez une zone valide.');

            return;
        }

        [$retraitLat, $retraitLng] = $this->position($retrait, $this->retraitLatitude, $this->retraitLongitude);
        [$livraisonLat, $livraisonLng] = $this->position($livraison, $this->livraisonLatitude, $this->livraisonLongitude);

        $options = $this->options();

        try {
            $commande = $creer->executer(auth()->user(), [
                'type' => TypeCommande::from($this->type),
                'retrait' => [
                    'zone_id' => $retrait->id,
                    'adresse_id' => null,
                    'latitude' => $retraitLat,
                    'longitude' => $retraitLng,
                    'repere' => $this->retraitRepere,
                    'contact_nom' => null,
                    'contact_telephone' => $this->retraitTelephone,
                    'epingle' => $this->retraitLatitude !== null && $this->retraitLongitude !== null,
                ],
                'livraison' => [
                    'zone_id' => $livraison->id,
                    'adresse_id' => null,
                    'latitude' => $livraisonLat,
                    'longitude' => $livraisonLng,
                    'repere' => $this->livraisonRepere,
                    'contact_nom' => null,
                    'contact_telephone' => $this->livraisonTelephone,
                    'epingle' => $this->livraisonLatitude !== null && $this->livraisonLongitude !== null,
                ],
                'description' => $this->description,
                'photo_chemin' => null,
                'mode_paiement' => $this->modeEspeces(),
                'express' => $options->express,
                'double_course' => $options->doubleCourse,
                'speciale' => $options->speciale,
                'nombre_repas' => $options->nombreRepas,
                'montant_articles' => 0,
            ]);
        } catch (LivraisonNonDisponibleException | TarifIntrouvableException | DomainException $e) {
            $this->addError('livraisonZoneId', $e->getMessage());

            return;
        }

        $this->redirectRoute('client.commande', ['commande' => $commande->reference]);
    }

    public function render(): View
    {
        return view('livewire.client.nouvelle-commande');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        $limites = config('bls.carte_limites');
        $latitude = ['nullable', 'numeric', 'between:' . implode(',', $limites['latitude'])];
        $longitude = ['nullable', 'numeric', 'between:' . implode(',', $limites['longitude'])];
        $zoneActive = Rule::exists('zones', 'id')->where('est_active', true);
        $telephone = ['required', 'regex:/^[0-9 +]{10,16}$/'];

        return [
            'type' => ['required', Rule::enum(TypeCommande::class)],
            'retraitZoneId' => ['required', $zoneActive],
            'retraitRepere' => ['required', 'string', 'max:255'],
            'retraitTelephone' => $telephone,
            'livraisonZoneId' => ['required', $zoneActive],
            'livraisonRepere' => ['required', 'string', 'max:255'],
            'livraisonTelephone' => $telephone,
            'description' => ['required', 'string', 'max:1000'],
            'nombreRepas' => ['required', 'integer', 'min:1', 'max:20'],
            'retraitLatitude' => [...$latitude, 'required_with:retraitLongitude'],
            'retraitLongitude' => [...$longitude, 'required_with:retraitLatitude'],
            'livraisonLatitude' => [...$latitude, 'required_with:livraisonLongitude'],
            'livraisonLongitude' => [...$longitude, 'required_with:livraisonLatitude'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'required' => 'Ce champ est obligatoire.',
            'retraitTelephone.regex' => 'Entrez un numéro valide, par exemple 07 00 00 00 00.',
            'livraisonTelephone.regex' => 'Entrez un numéro valide, par exemple 07 00 00 00 00.',
            'between' => 'Ce point est trop éloigné d\'Adzopé. Replacez l\'épingle.',
            'numeric' => 'Position invalide. Replacez l\'épingle.',
            'required_with' => 'Replacez l\'épingle sur la carte.',
        ];
    }

    private function zone(string $id): ?Zone
    {
        return $id === '' ? null : Zone::query()->where('est_active', true)->find((int) $id);
    }

    private function options(): OptionsCommande
    {
        return new OptionsCommande(
            express: $this->express,
            doubleCourse: $this->doubleCourse,
            speciale: $this->speciale,
            nombreRepas: $this->type === 'repas' ? max(1, (int) $this->nombreRepas) : 1,
        );
    }

    /**
     * Épingle du client si elle existe, sinon le centre de la zone.
     *
     * @return array{0: float, 1: float}
     */
    private function position(Zone $zone, ?float $latitude, ?float $longitude): array
    {
        if ($latitude !== null && $longitude !== null) {
            return [$latitude, $longitude];
        }

        return [
            (float) ($zone->centre_latitude ?? config('bls.centre_defaut.latitude')),
            (float) ($zone->centre_longitude ?? config('bls.centre_defaut.longitude')),
        ];
    }

    private function modeEspeces(): ModePaiement
    {
        return collect(ModePaiement::cases())->first(fn (ModePaiement $mode): bool => ! $mode->estMobileMoney());
    }
}
