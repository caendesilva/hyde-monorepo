<div id="sidebar-brand" class="flex items-center justify-between h-16 py-4 px-2">
    <strong class="px-2">
        @if(DocumentationPage::home())
            <a href="{{ DocumentationPage::home() }}">
                {{ $sidebar->getHeader() }}
            </a>
        @else
            {{ $sidebar->getHeader() }}
        @endif
    </strong>
    <x-hyde::navigation.theme-toggle-button class="opacity-75 hover:opacity-100"/>
</div>