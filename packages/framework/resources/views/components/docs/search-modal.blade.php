<button id="search-menu-button" x-on:click="searchWindowOpen = ! searchWindowOpen"
        :title="searchWindowOpen ? 'Close search window' : 'Open search window'; $nextTick(() => { setTimeout(() => { document.getElementById('search-input').focus(); }); });"
        class="absolute right-4 top-4 mr-4 z-10 opacity-75 hover:opacity-100 hidden md:block" aria-label="Toggle search window">
    <span x-show="! searchWindowOpen">
        Search <svg class="float-left mr-1 dark:fill-white" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" role="presentation">
            <path d="M0 0h24v24H0z" fill="none"/>
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
    </span>
    <span x-show="searchWindowOpen">
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
    </span>
</button>
<button id="search-menu-button-mobile" x-on:click="searchWindowOpen = ! searchWindowOpen"
        :title="searchWindowOpen ? 'Close search window' : 'Open search window'; $nextTick(() => { setTimeout(() => { document.getElementById('search-input').focus(); }); });"
        class="block md:hidden fixed bottom-4 right-4 z-10 rounded-full p-2 opacity-75 hover:opacity-100 fill-black bg-gray-200 dark:fill-gray-200 dark:bg-gray-700 print:hidden"
        aria-label="Toggle search menu">
    <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24" role="presentation">
        <path d="M0 0h24v24H0z" fill="none"/>
        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
    </svg>
</button>

<div id="search-window-container" x-show="searchWindowOpen" x-cloak role="dialog" class="z-30 fixed top-0 left-0 w-screen h-screen flex flex-col items-center px-8 py-24 md:py-16">
    <aside x-on:click.away="searchWindowOpen = false" id="search-menu"
           class="bg-white dark:bg-gray-800 z-50 p-4 rounded-lg overflow-y-hidden
        min-h-[300px] max-h-[75vh] w-[70ch] max-w-full cursor-auto
        flex flex-col gap-4">

        <header class="flex justify-between items-center border-b dark:border-gray-700 pb-3 md:hidden">
            <strong>Search the documentation site</strong>
            <button @click="searchWindowOpen = false" title="Close search window"
                    class="opacity-75 hover:opacity-100" aria-label="Close search window">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </header>

        <div class="flex-grow">
            <x-hyde::docs.hyde-search />
        </div>

        <footer x-data="{ hasResults: false }" @search-results-updated.window="hasResults = $event.detail.hasResults"
                class="prose dark:prose-invert text-center font-mono hidden sm:block"
                x-show="!hasResults">
            <small>
                Press <code class="p-0"><kbd title="Forward slash" class="shadow-none">/</kbd></code> to open search window.
                Use <code class="p-0"><kbd title="Escape key" class="shadow-none">esc</kbd></code> to close.
            </small>
        </footer>
    </aside>

    <div id="search-window-backdrop" title="Click to close search window" class="w-screen h-screen cursor-pointer z-40 bg-black/50 absolute top-0"></div>
</div>