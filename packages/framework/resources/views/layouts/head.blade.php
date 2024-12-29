<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $page->title() }}</title>

@if (Asset::exists('favicon.ico'))
    <link rel="shortcut icon" href="{{ Asset::get('media/favicon.ico') }}" type="image/x-icon">
@endif

{{-- App Meta Tags --}}
@include('hyde::layouts.meta')

{{-- App Stylesheets --}}
@include('hyde::layouts.styles')

@if(Features::hasDarkmode())
    {{-- Check the local storage for theme preference to avoid FOUC --}}
    <meta id="meta-color-scheme" name="color-scheme" content="{{ config('hyde.default_color_scheme', 'light') }}">
    <script>if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { document.documentElement.classList.add('dark'); document.getElementById('meta-color-scheme').setAttribute('content', 'dark');} else { document.documentElement.classList.remove('dark') } </script>
@endif

{{-- Add any extra code to include before the closing <head> tag --}}
@stack('head')

{{-- If the user has defined any custom head tags, render them here --}}
{!! config('hyde.head') !!}
{!! Includes::html('head') !!}
