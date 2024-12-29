@php
    $article ??= \Hyde\Framework\Features\Documentation\SemanticDocumentationArticle::make($page);
@endphp

<article id="document" itemscope itemtype="https://schema.org/Article" @class([
        'mx-auto lg:ml-8 max-w-3xl p-12 md:px-16 max-w-[1000px] min-h-[calc(100vh_-_4rem)]',
        config('markdown.prose_classes', 'prose dark:prose-invert'),
    ])>
    @yield('content')

    @if ($article)
        <header id="document-header" class="flex items-center flex-wrap justify-between prose-h1:mb-3">
            {{ $article->renderHeader() }}
        </header>
        <section id="document-main-content" itemprop="articleBody">
            {{ $article->renderBody() }}
        </section>
        <footer id="document-footer" class="flex items-center flex-wrap mt-8 prose-p:my-3 justify-between text-[90%]">
            {{ $article->renderFooter() }}
        </footer>
    @endif
</article>