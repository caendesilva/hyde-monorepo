@php
    $sidebar = app('navigation.sidebar');
@endphp

<aside id="sidebar" x-cloak :class="sidebarOpen ? 'visible left-0' : 'invisible -left-64 md:visible md:left-0'" class="bg-gray-100 dark:bg-gray-800 dark:text-gray-200 h-screen w-64 fixed z-30 md:flex flex-col shadow-lg md:shadow-none transition-all duration-300">
    <header id="sidebar-header" class="h-16">
        @include('hyde::components.docs.sidebar-brand')
    </header>
    <nav id="sidebar-navigation" class="p-2 overflow-y-auto border-y border-gray-300 dark:border-[#1b2533] h-full">
        @include('hyde::components.docs.sidebar-items')
    </nav>
    @if($sidebar->hasFooter())
        <footer id="sidebar-footer" class="h-16 p-4 w-full bottom-0 left-0 text-center leading-8">
            @include('hyde::components.docs.sidebar-footer-text')
        </footer>
    @endif
</aside>