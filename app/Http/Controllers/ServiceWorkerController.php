<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ServiceWorkerController
{
    public function __invoke(): BinaryFileResponse
    {
        $fichier = public_path('build/sw.js');

        abort_unless(is_file($fichier), 404);

        return response()->file($fichier, [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache, max-age=0',
        ]);
    }
}
