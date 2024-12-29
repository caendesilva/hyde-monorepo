{{-- The compiled Vite scripts --}}
@if(Vite::running())
    {{ Vite::assets(['resources/assets/app.js']) }}
@elseif(Asset::exists('app.js'))
    <script defer src="{{ Asset::get('app.js') }}"></script>
@endif

{{-- Alpine.js --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.10.3/dist/cdn.min.js" integrity="sha256-gOkV4d9/FmMNEkjOzVlyM2eNAWSUXisT+1RbMTTIgXI=" crossorigin="anonymous"></script>

<script>
    function toggleTheme() {
        if (localStorage.getItem('color-theme') === 'dark' || !('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.remove("dark");
            localStorage.setItem('color-theme', 'light');
            document.getElementById('meta-color-scheme').setAttribute('content', 'light');
        } else {
            document.documentElement.classList.add("dark");
            localStorage.setItem('color-theme', 'dark');
            document.getElementById('meta-color-scheme').setAttribute('content', 'dark');
        }
    }
</script>

{{-- Add any extra scripts to include before the closing <body> tag --}}
@stack('scripts')

{{-- If the user has defined any custom scripts, render them here --}}
{!! config('hyde.scripts') !!}
{!! Includes::html('scripts') !!}
