{{-- Zone du bouton principal : collée en bas de l'écran sur téléphone, à portée du pouce --}}
<div class="sticky bottom-0 mt-8 border-t border-trait bg-white px-5 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] lg:static lg:mt-6 lg:border-0 lg:px-0 lg:pb-0">
    {{ $slot }}
</div>