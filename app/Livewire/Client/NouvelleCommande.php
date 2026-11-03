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
        $retrait = $this->zones->firstWhere('id', (int) $this->retraitZoneId);
        $livraison = $this->zones->firstWhere('id', (int) $this->livraisonZoneId);

        if ($retrait === null || $livraison === null) {
            return ['prix' => null, 'erreur' => null];
        }

        [$retraitLat, $retraitLng] = $this->position($retrait);
        [$livraisonLat, $livraisonLng] = $this->position($livraison);

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
        } catch (LivraisonNonDisponibleException|TarifIntrouvableException|DomainException $e) {
            return ['prix' => null, 'erreur' => $e->getMessage()];
        }

        return ['prix' => $prix, 'erreur' => null];
    }

    public function enregistrer(CreerCommandeAction $creer): void
    {
        $this->validate();

        $retrait = $this->zones->firstWhere('id', (int) $this->retraitZoneId);
        $livraison = $this->zones->firstWhere('id', (int) $this->livraisonZoneId);
        [$retraitLat, $retraitLng] = $this->position($retrait);
        [$livraisonLat, $livraisonLng] = $this->position($livraison);

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
                ],
                'livraison' => [
                    'zone_id' => $livraison->id,
                    'adresse_id' => null,
                    'latitude' => $livraisonLat,
                    'longitude' => $livraisonLng,
                    'repere' => $this->livraisonRepere,
                    'contact_nom' => null,
                    'contact_telephone' => $this->livraisonTelephone,
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
        } catch (LivraisonNonDisponibleException|TarifIntrouvableException|DomainException $e) {
            $this->addError('livraisonZoneId', $e->getMessage());

            return;
        }

        $this->redirectRoute('client.commande', ['commande' => $commande->reference]);
    }

    public function render()
    {
        return view('livewire.client.nouvelle-commande');
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
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
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'required' => 'Ce champ est obligatoire.',
            'retraitTelephone.regex' => 'Entrez un numéro valide, par exemple 07 00 00 00 00.',
            'livraisonTelephone.regex' => 'Entrez un numéro valide, par exemple 07 00 00 00 00.',
        ];
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

    /** Position provisoire : le centre de la zone, remplacé par l'épingle à l'étape 4. */
    private function position(Zone $zone): array
    {
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
