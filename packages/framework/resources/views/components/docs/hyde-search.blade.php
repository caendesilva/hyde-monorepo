@props(['modal' => true])

<div id="hyde-search" x-data="hydeSearch">
    <template id="search-highlight-template">
        <mark class="bg-yellow-400 dark:bg-yellow-300"></mark>
    </template>

    <div class="relative">
        <input type="search" name="search" id="search-input" x-model="searchTerm" @input="search()" placeholder="Search..." autocomplete="off" autofocus
                {{ $attributes->merge(['class' => 'w-full rounded text-base leading-normal bg-gray-100 dark:bg-gray-700 py-2 px-3']) }}
        >

        <div x-show="isLoading" class="absolute right-3 top-2.5">
            <div class="animate-spin h-5 w-5 border-2 border-gray-500 rounded-full border-t-transparent"></div>
        </div>
    </div>

    <div x-show="searchTerm" class="mt-4">
        <p x-text="statusMessage" class="text-sm text-gray-600 dark:text-gray-400 mb-2 pb-2"></p>

        <dl class="space-y-4 -mt-4 pl-2 -ml-2 {{ $modal ? 'max-h-[60vh] overflow-x-hidden overflow-y-auto' : '' }}">
            <template x-for="result in results" :key="result.slug">
                <div>
                    <dt>
                        <a :href="result.destination" x-text="result.title" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"></a><span class="text-sm text-gray-600 dark:text-gray-400" x-text="`, ${result.matches} occurrence${result.matches !== 1 ? 's' : ''} found.`"></span>
                    </dt>
                    <dd class="mt-1 text-sm text-gray-700 dark:text-gray-300" x-html="result.context"></dd>
                </div>
            </template>
        </dl>
    </div>

    <script>
		{!! file_get_contents(file_exists(Hyde::path('resources/js/HydeSearch.js')) 
			? Hyde::path('resources/js/HydeSearch.js')
			: Hyde::vendorPath('resources/js/HydeSearch.js')
		) !!}
        
        document.addEventListener('alpine:init', () => {
            Alpine.data('hydeSearch', () => 
                initHydeSearch('{{ Hyde::relativeLink(\Hyde\Framework\Features\Documentation\DocumentationSearchIndex::outputPath()) }}')
            );
        });
    </script>
</div>
