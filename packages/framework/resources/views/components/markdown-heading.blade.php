@props([
    'level' => 1,
    'id' => null,
    'extraAttributes' => [],
    'addPermalink' => config('markdown.permalinks.enabled', true),
])

@php
    $tag = 'h' . $level;
    $id = $id ?? \Illuminate\Support\Str::slug($slot);

    if ($addPermalink === true) {
        $extraAttributes['id'] = $id;

        isset($extraAttributes['class']) ? $extraAttributes['class'] .= ' group w-fit' : $extraAttributes['class'] = 'group w-fit';
    }
@endphp

<{{ $tag }} {{ $attributes->merge([...$extraAttributes]) }}>
    {!! $slot !!}
    @if($addPermalink === true)
        <a href="#{{ $id }}" class="heading-permalink opacity-0 ml-1 transition-opacity duration-300 ease-linear px-1 group-hover:opacity-100 focus:opacity-100 group-hover:grayscale-0 focus:grayscale-0" title="Permalink">
            #
        </a>
    @endif
</{{ $tag }}>
