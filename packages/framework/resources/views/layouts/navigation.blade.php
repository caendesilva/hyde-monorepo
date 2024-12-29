@php
    $navigation = app('navigation.main');
@endphp

<nav aria-label="Main navigation" id="main-navigation" class="flex flex-wrap items-center justify-between p-4 shadow-lg sm:shadow-xl md:shadow-none dark:bg-gray-800">
    <div id="main-navigation-brand" class="flex flex-grow items-center flex-shrink-0 text-gray-700 dark:text-gray-200">
        @include('hyde::components.navigation.navigation-brand')

        <div class="ml-auto">
            <x-hyde::navigation.theme-toggle-button/>
        </div>
    </div>

    <div class="block md:hidden">
        <button id="navigation-toggle-button" class="flex items-center px-3 py-1 hover:text-gray-700 dark:text-gray-200"
                aria-label="Toggle navigation menu" @click="navigationOpen = ! navigationOpen">
            <svg x-show="! navigationOpen" title="Open Navigation Menu" class="dark:fill-gray-200"
                 style="display: block;"
                 id="open-main-navigation-menu-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24"
                 width="24">
                <title>Open Menu</title>
                <path d="M0 0h24v24H0z" fill="none"/>
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
            <svg x-show="navigationOpen" title="Close Navigation Menu" class="dark:fill-gray-200" style="display: none;"
                 id="close-main-navigation-menu-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24"
                 width="24">
                <title>Close Menu</title>
                <path d="M0 0h24v24H0z" fill="none"></path>
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
            </svg>
        </button>
    </div>

    <div id="main-navigation-links" 
         class="w-full hidden md:flex flex-grow md:flex-grow-0 md:items-center md:w-auto px-6 -mx-4 border-t mt-3 pt-3 md:border-none md:mt-0 md:py-0 border-gray-200 dark:border-gray-700"
         :class="navigationOpen ? '!block' : ''">
        <ul aria-label="Navigation links" class="md:flex-grow md:flex justify-end">
            @foreach ($navigation->getItems() as $item)
                <li class="md:mx-2">
                    @if($item instanceof \Hyde\Framework\Features\Navigation\NavigationGroup)
                        <x-hyde::navigation.dropdown :label="$item->getLabel()" :items="$item->getItems()"/>
                    @else
                        <x-hyde::navigation.navigation-link :item="$item"/>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</nav>