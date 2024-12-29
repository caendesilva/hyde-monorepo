{{-- Prevent Alpine.js flashes --}}
<style>[x-cloak] {display: none!important}</style>

{{-- The compiled Tailwind/App styles --}}
@if(Vite::running())
    {{ Vite::assets(['resources/assets/app.css']) }}
@else
    @if(config('hyde.load_app_styles_from_cdn', false))
        <link rel="stylesheet" href="{{ HydeFront::cdnLink('app.css') }}">
    @elseif(Asset::exists('app.css'))
        <link rel="stylesheet" href="{{ Asset::get('app.css') }}">
    @endif


    {{-- Dynamic TailwindCSS Play CDN --}}
    @if(config('hyde.use_play_cdn', false))
        <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
        <script>tailwind.config = { {!! HydeFront::injectTailwindConfig() !!} }</script>
        <script>console.warn('The HydePHP TailwindCSS Play CDN is enabled. This is for development purposes only and should not be used in production.', 'See https://hydephp.com/docs/1.x/managing-assets');</script>
    @endif
@endif

{{-- Add any extra styles to include after the others --}}
@stack('styles')