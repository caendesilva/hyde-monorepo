/**
 * This file provides support for the Alpine.js search component.
 *
 * It's automatically loaded by the hyde-search.blade.php component.
 *
 * It can be replaced by a custom implementation, at the developer's own risk,
 * by providing a new compatible implementation in ./resources/js/HydeSearch.js.
 *
 * @param {string} searchIndexUrl
 * @returns {Object}
 */
function initHydeSearch(searchIndexUrl) {
    return {
        searchIndex: [],
        searchTerm: '',
        results: [],
        isLoading: true,
        statusMessage: '',

        async init() {
            const response = await fetch(searchIndexUrl);
            if (!response.ok) {
                console.error('Could not load search index');
                return;
            }
            this.searchIndex = await response.json();
            this.isLoading = false;
        },

        search() {
            const startTime = performance.now();
            this.results = [];

            if (!this.searchTerm) {
                this.statusMessage = '';
                window.dispatchEvent(new CustomEvent('search-results-updated', { detail: { hasResults: false } }));
                return;
            }

            const searchResults = this.searchIndex.filter(entry =>
                entry.title.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                entry.content.toLowerCase().includes(this.searchTerm.toLowerCase())
            );

            if (searchResults.length === 0) {
                this.statusMessage = 'No results found.';
                window.dispatchEvent(new CustomEvent('search-results-updated', { detail: { hasResults: false } }));
                return;
            }

            const totalMatches = searchResults.reduce((acc, result) => {
                return acc + (result.content.match(new RegExp(this.searchTerm, 'gi')) || []).length;
            }, 0);

            searchResults.sort((a, b) => {
                return (b.content.match(new RegExp(this.searchTerm, 'gi')) || []).length
                    - (a.content.match(new RegExp(this.searchTerm, 'gi')) || []).length;
            });

            this.results = searchResults.map(result => {
                const matches = (result.content.match(new RegExp(this.searchTerm, 'gi')) || []).length;
                const context = this.getSearchContext(result.content);
                return { ...result, matches, context };
            });

            const timeMs = Math.round((performance.now() - startTime) * 100) / 100;
            this.statusMessage = `Found ${totalMatches} result${totalMatches !== 1 ? 's' : ''} in ${searchResults.length} pages. ~${timeMs}ms`;

            window.dispatchEvent(new CustomEvent('search-results-updated', { detail: { hasResults: true } }));
        },

        getSearchContext(content) {
            const searchTermPos = content.toLowerCase().indexOf(this.searchTerm.toLowerCase());
            const sentenceStart = content.lastIndexOf('.', searchTermPos) + 1;
            const sentenceEnd = content.indexOf('.', searchTermPos) + 1;
            const sentence = content.substring(sentenceStart, sentenceEnd).trim();
            const template = document.getElementById('search-highlight-template');

            return sentence.replace(
                new RegExp(this.searchTerm, 'gi'),
                match => {
                    const mark = template.content.querySelector('mark').cloneNode();
                    mark.textContent = match;
                    return mark.outerHTML;
                }
            );
        }
    };
}
