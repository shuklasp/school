document.addEventListener('DOMContentLoaded', () => {
    // Sidebar expand/collapse functionality
    const sidebarLinks = document.querySelectorAll('.sidebar-link');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const submenu = link.nextElementSibling; // Get the submenu div
            if (submenu.style.display === 'block') {
                submenu.style.display = 'none'; // Collapse
            } else {
                // Close any other open submenus
                document.querySelectorAll('.submenu').forEach(sub => {
                    sub.style.display = 'none';
                });
                submenu.style.display = 'block'; // Expand
            }
        });
    });

    // Async content loading functionality
    const mainContent = document.querySelector('main');
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'loading';
    loadingIndicator.textContent = 'Loading...';
    mainContent.insertAdjacentElement('beforeend', loadingIndicator);

    // Function to load content asynchronously
    async function loadContent(url) {
        try {
            loadingIndicator.classList.add('active');
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok');
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('main').innerHTML;
            mainContent.innerHTML = newContent; // Update main content
            loadingIndicator.classList.remove('active');
            updateActiveLink(url);
        } catch (error) {
            console.error('Error loading content:', error);
            mainContent.innerHTML = '<p>Error loading content. Please try again later.</p>';
            loadingIndicator.classList.remove('active');
        }
    }

    // Update active link in navigation
    function updateActiveLink(url) {
        const path = url.split('/').pop();
        document.querySelectorAll('.nav-link, .sidebar-link, .submenu-link, .icon').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-href') === path || (link.tagName === 'IMG' && link.getAttribute('data-href') === path)) {
                link.classList.add('active');
                if (link.tagName === 'A') link.closest('li')?.querySelector('a')?.classList.add('active');
            }
        });
    }

    // Handle clicks on navigation, sidebar, submenu, and icons
    document.querySelectorAll('.nav-link, .sidebar-link, .submenu-link, .icon').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const href = link.getAttribute('data-href') || (link.tagName === 'IMG' ? link.getAttribute('data-href') : null);
            if (href) {
                loadContent(href);
            }
        });
    });

    // Initial load (optional: load home content by default)
    loadContent('home.html');
});