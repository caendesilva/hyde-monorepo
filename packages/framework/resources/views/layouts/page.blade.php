{{-- The Markdown Page Layout --}}
@extends('hyde::layouts.app')
@section('content')

    <main id="content" class="mx-auto max-w-7xl py-16 px-8">
        <article @class(['mx-auto', config('markdown.prose_classes', 'prose dark:prose-invert')])>
            {{ $content }}
        </article>
    </main>

@endsection