@props(['items', 'isChild' => false])

@if(! empty($items))
    <ul @class([$isChild ? 'pl-2' : 'table-of-contents pb-3'])>
        @foreach($items as $item)
            <li class="my-0.5">
                @if(isset($item['identifier']))
                    <a href="#{{ $item['identifier'] }}" class="-ml-8 pl-8 opacity-80 hover:opacity-100 hover:bg-gray-200/20 transition-all duration-300">
                        <span class="text-[75%] opacity-50 hover:opacity-100 transition-opacity duration-300">#</span>
                        {{ $item['title'] }}
                    </a>
                @endif

                @if(! empty($item['children']))
                    <x-hyde::docs.table-of-contents :items="$item['children']" :isChild="true" />
                @endif
            </li>
        @endforeach
    </ul>
@endif