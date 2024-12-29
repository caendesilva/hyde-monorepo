@extends('hyde::layouts.docs')
@section('content')
    @php
        $title = Config::getString('docs.sidebar.header', 'Documentation');

        $searchTitle = str_ends_with(strtolower($title), ' docs')
            ? 'Search the ' . substr($title, 0, -5) . ' Documentation'
            : 'Search ' . $title;
    @endphp
    <h1>{{ $searchTitle }}</h1>
    <style>#search-menu-button, .edit-page-link { display: none !important; }</style>
    <div class="not-prose">
        <x-hyde::docs.hyde-search class="max-w-sm" :modal="false" />
    </div>
@endsection
