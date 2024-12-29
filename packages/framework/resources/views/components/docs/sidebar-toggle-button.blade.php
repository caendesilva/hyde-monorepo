<button 
    id="sidebar-toggle" 
    title="Toggle sidebar" 
    aria-label="Toggle sidebar navigation menu"
    @click="sidebarOpen = !sidebarOpen"
    class="flex items-center justify-center w-8 h-8 px-2 py-1 hover:text-gray-700 dark:text-gray-200 opacity-75 hover:opacity-100"
  >
    <div class="relative w-5 h-4">
        <div class="absolute top-0 w-5 h-0.5 bg-current transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'opacity-0' : ''"></div>
        <div class="absolute inset-0 my-auto w-5 h-0.5 bg-current transition-all duration-300 ease-in-out origin-center" :class="sidebarOpen ? 'rotate-45' : ''"></div>
        <div class="absolute inset-0 my-auto w-5 h-0.5 bg-current transition-all duration-300 ease-in-out origin-center" :class="sidebarOpen ? '-rotate-45' : ''"></div>
        <div class="absolute bottom-0 w-5 h-0.5 bg-current transition-all duration-300 ease-in-out" :class="sidebarOpen ? 'opacity-0' : ''"></div>
    </div>
</button>