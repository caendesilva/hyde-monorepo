/**
 * Core Scripts for the HydePHP Frontend
 * 
 * @package     HydePHP - HydeFront
 * @version     v0.4.0 (HydeFront)
 * @author      Caen De Silva
 */

// Handle the main navigation menu

const mainNavigation = document.getElementById("main-navigation");
const mainNavigationLinks = document.getElementById("main-navigation-links");
const openMainNavigationMenuIcon = document.getElementById("open-main-navigation-menu-icon");
const closeMainNavigationMenuIcon = document.getElementById("close-main-navigation-menu-icon");

let navigationOpen = false;

// Toggle the navigation menu visibility when the menu button is clicked
function toggleNavigation() {
	if (navigationOpen) {
		hideNavigation();
	} else {
		showNavigation();
	}
}

// Show the navigation menu items
function showNavigation() {
	mainNavigationLinks.classList.remove("hidden");
	openMainNavigationMenuIcon.style.display = "none";
	closeMainNavigationMenuIcon.style.display = "block";
	 
	navigationOpen = true;
}

// Hide the navigation menu items
function hideNavigation() {
	mainNavigationLinks.classList.add("hidden");
	openMainNavigationMenuIcon.style.display = "block";
	closeMainNavigationMenuIcon.style.display = "none";
	navigationOpen = false;
}

// Handle the documentation page sidebar

var sidebarOpen = screen.width >= 768;

const sidebar = document.getElementById("documentation-sidebar");
const main = document.getElementById("documentation-content");
const backdrop = document.getElementById("sidebar-backdrop");

const toggleButtons = document.querySelectorAll(".sidebar-button-wrapper");

function toggleSidebar() {
	if (sidebarOpen) {
		hideSidebar();
	} else {
		showSidebar();
	}
}

function showSidebar() {
	sidebar.classList.remove("hidden");
	sidebar.classList.add("flex");
	backdrop.classList.remove("hidden");
	document.getElementById("app").style.overflow = "hidden";

	toggleButtons.forEach((button) => {
		button.classList.remove("open");
		button.classList.add("closed");
	});

	sidebarOpen = true;
}

function hideSidebar() {
	sidebar.classList.add("hidden");
	sidebar.classList.remove("flex");
	backdrop.classList.add("hidden");
	document.getElementById("app").style.overflow = null;

	toggleButtons.forEach((button) => {
		button.classList.add("open");
		button.classList.remove("closed");
	});

	sidebarOpen = false;
}

// Component by https://flowbite.com/docs/customize/dark-mode/ (License MIT)
var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon'); // Change the icons inside the button based on previous settings

if (localStorage.getItem('color-theme') === 'dark' || !('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches) {
  themeToggleLightIcon.classList.remove('hidden');
} else {
  themeToggleDarkIcon.classList.remove('hidden');
}

var themeToggleBtn = document.getElementById('theme-toggle');
themeToggleBtn.addEventListener('click', function () {
  // toggle icons inside button
  themeToggleDarkIcon.classList.toggle('hidden');
  themeToggleLightIcon.classList.toggle('hidden'); // if set via local storage previously

  if (localStorage.getItem('color-theme')) {
    if (localStorage.getItem('color-theme') === 'light') {
      document.documentElement.classList.add('dark');
      localStorage.setItem('color-theme', 'dark');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('color-theme', 'light');
    } // if NOT set via local storage previously

  } else {
    if (document.documentElement.classList.contains('dark')) {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('color-theme', 'light');
    } else {
      document.documentElement.classList.add('dark');
      localStorage.setItem('color-theme', 'dark');
    }
  }
});
