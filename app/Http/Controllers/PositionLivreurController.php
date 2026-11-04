<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EnregistrerPositionRequest;
use App\Services\PositionLivreurService;
use Illuminate\Http\Response;

final class PositionLivreurController
{
    public function __invoke(EnregistrerPositionRequest $requete, PositionLivreurService $positions): Response
    {
        $raison = $positions->enregistrer(
            $requete->user(),
            (float) $requete->validated('latitude'),
            (float) $requete->validated('longitude'),
            $requete->validated('precision_m') !== null ? (float) $requete->validated('precision_m') : null,
            (int) $requete->validated('age_ms'),
        );

        $reponse = response()->noContent();

        // Le filtrage des sauts de position est normal : seul l'écartement utile est signalé au téléphone
        if ($raison !== null && $raison !== 'filtre') {
            $reponse->header('X-Position-Ecartee', $raison);
        }

        return $reponse;
    }
}
