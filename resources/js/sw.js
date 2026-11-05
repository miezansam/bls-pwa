import { clientsClaim } from 'workbox-core';
import { cleanupOutdatedCaches, matchPrecache, precacheAndRoute } from 'workbox-precaching';
import { registerRoute, setCatchHandler } from 'workbox-routing';
import { NetworkOnly } from 'workbox-strategies';

self.skipWaiting();
clientsClaim();

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

// Les pages ne sont JAMAIS mises en cache : elles contiennent le code de livraison,
// l'adresse du client et un jeton CSRF. Seule la page « Pas de connexion » est gardée.
registerRoute(({ request }) => request.mode === 'navigate', new NetworkOnly());

setCatchHandler(async ({ request }) => {
    if (request.mode === 'navigate') {
        return (await matchPrecache('/hors-ligne')) ?? Response.error();
    }

    return Response.error();
});