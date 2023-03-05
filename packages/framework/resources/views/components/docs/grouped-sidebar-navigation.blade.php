@php /** @var \Hyde\Framework\Features\Navigation\DocumentationSidebar $sidebar */ @endphp
@php ($collapsible = config('docs.sidebar.collapsible', true))
<ul id="sidebar-navigation-items" role="list">
    @foreach ($sidebar->getGroups() as $group)
        <li class="sidebar-navigation-group" role="listitem" @if ($collapsible) x-data="{ groupOpen: {{ $sidebar->isGroupActive($group) ? 'true' : 'false' }} }" @endif>
            <header class="sidebar-navigation-group-header p-2 px-4 -ml-2 flex justify-between items-center @if ($collapsible) group hover:bg-black/10 @endif" @if ($collapsible) @click="groupOpen = ! groupOpen" @endif>
                <h4 class="sidebar-navigation-group-heading text-base font-semibold @if ($collapsible) cursor-pointer dark:group-hover:text-white @endif">{{ Hyde::makeTitle($group) }}</h4>
                @if ($collapsible)
                    <button class="sidebar-navigation-group-toggle opacity-50 group-hover:opacity-100">
                        <svg class="sidebar-navigation-group-toggle-icon sidebar-navigation-group-toggle-icon-open" x-show="groupOpen" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 12L12 8L4 8L8 12Z" fill="currentColor" />
                        </svg>
                        <svg class="sidebar-navigation-group-toggle-icon sidebar-navigation-group-toggle-icon-closed" x-show="! groupOpen" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 8L8 12L8 4L12 8Z" fill="currentColor" />
                        </svg>
                    </button>
                @endif
            </header>
            <ul class="sidebar-navigation-group-list ml-4 px-2 mb-2" role="list" @if ($collapsible) x-show="groupOpen" @endif>
                @foreach ($sidebar->getItemsInGroup($group) as $item)
                    @include('hyde::components.docs.sidebar-item', ['grouped' => true])
                @endforeach
            </ul>
        </li>
    @endforeach
</ul>