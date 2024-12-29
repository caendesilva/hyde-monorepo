<article aria-label="Article" id="{{ $page->identifier }}" itemscope itemtype="https://schema.org/BlogPosting"
    @class(['post-article mx-auto', config('markdown.prose_classes', 'prose dark:prose-invert')])>
    <meta itemprop="identifier" content="{{ $page->identifier }}">
    @if($page->getCanonicalUrl() !== null)
        <meta itemprop="url" content="{{ $page->getCanonicalUrl() }}">
    @endif

    <header aria-label="Header section" role="doc-pageheader">
        <h1 itemprop="headline" class="mb-4">{{ $page->title }}</h1>
        <div id="byline" aria-label="About the post" role="doc-introduction">
            @includeWhen(isset($page->date), 'hyde::components.post.date')
            @includeWhen(isset($page->author), 'hyde::components.post.author')
            @includeWhen(isset($page->category), 'hyde::components.post.category')
        </div>
    </header>
    @includeWhen(isset($page->image), 'hyde::components.post.image')
    <div aria-label="Article body" itemprop="articleBody">
        {{ $content }}
    </div>
    <span class="sr-only">End of article</span>
</article>