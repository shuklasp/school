document.addEventListener('DOMContentLoaded', () => {
    // Sidebar expand/collapse functionality
    function setupSidebarEvents() {
        const sidebarLinks = document.querySelectorAll('.sidebar-link');

        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const submenu = link.nextElementSibling; // Get the submenu div
                if (submenu && submenu.classList.contains('submenu')) {
                    if (submenu.style.display === 'block') {
                        submenu.style.display = 'none'; // Collapse
                    } else {
                        // Close any other open submenus
                        document.querySelectorAll('.submenu').forEach(sub => {
                            sub.style.display = 'none';
                        });
                        submenu.style.display = 'block'; // Expand
                    }
                }
            });
        });
    }

    // Async content loading functionality (only used in index.php)
    const mainContent = document.querySelector('main');
    const sidebar = document.querySelector('.sidebar');
    const sidebarMenu = document.querySelector('.sidebar-menu');
    const container = document.querySelector('.container');
    const toggleSidebarBtn = document.querySelector('.toggle-sidebar-btn');
    const loadingIndicator = document.querySelector('.loading');
    const profileIcon = document.getElementById('profileIcon');
    const dropdownContent = document.getElementById('dropdownContent');

    // Profile dropdown toggle (only in index.php)
    if (profileIcon) {
        profileIcon.addEventListener('click', (e) => {
            e.preventDefault();
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown if clicking outside
        document.addEventListener('click', (e) => {
            if (!profileIcon.contains(e.target) && !dropdownContent.contains(e.target)) {
                dropdownContent.style.display = 'none';
            }
        });
    }

    // Function to load content asynchronously (used in index.php)
    async function loadContent(url, targetElement, isSidebar = false) {
        try {
            if (!url) {
                throw new Error('No URL provided for loading content');
            }
            console.log(`Loading ${isSidebar ? 'sidebar' : 'main'} content from: ${url}`); // Debugging
            loadingIndicator.style.display = 'block';
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status} for URL: ${url}`);
            }
            const html = await response.text();
            console.log(`Raw HTML response for ${url}:`, html); // Debugging: Log the raw HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            let newContent;
            if (isSidebar) {
                const asideElement = doc.querySelector('aside');
                if (!asideElement) {
                    console.error(`No <aside> element found in ${url}. HTML:`, html);
                    newContent = '<p>No sidebar menu found.</p>';
                } else {
                    newContent = asideElement.innerHTML;
                    console.log('Sidebar content loaded:', newContent); // Debugging
                }
            } else {
                newContent = doc.querySelector('main')?.innerHTML || '<p>No content found.</p>';
                console.log('Main content loaded:', newContent); // Debugging
            }
            targetElement.innerHTML = newContent; // Update target element
            loadingIndicator.style.display = 'none';
            if (isSidebar) {
                setupSidebarEvents(); // Reattach sidebar events after loading
            }
            updateActiveLink(url, isSidebar);
            setupFormEvents(); // Reattach form event listeners after loading new content
        } catch (error) {
            console.error('Error loading content:', error);
            targetElement.innerHTML = isSidebar ? '<p>Error loading sidebar menu. Please try again later.</p>'
                : '<p>Error loading content. Please try again later.</p>';
            loadingIndicator.style.display = 'none';
        }
    }

    // Update active link in navigation (used in index.php)
    function updateActiveLink(url, isSidebar = false) {
        const path = url.split('/').pop();
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-href') === path) {
                link.classList.add('active');
            }
        });
        document.querySelectorAll('.sidebar-link, .submenu-link').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-href') === path) {
                link.classList.add('active');
                link.closest('li')?.querySelector('a')?.classList.add('active');
            }
        });
    }

    // Handle form submissions (used in both login.php and index.php)
    function setupFormEvents() {
        const forms = document.querySelectorAll('.submission-form');
        forms.forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const action = form.getAttribute('data-action');
                const formData = new FormData(form);
                formData.append('action', action); // Add the action to the form data

                try {
                    console.log(`Submitting form with action: ${action}`); // Debugging
                    loadingIndicator ? loadingIndicator.style.display = 'block' : null;
                    const response = await fetch('submit.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    const result = await response.json();
                    console.log('Form submission response:', result); // Debugging

                    // Update the form response area
                    const formResponse = form.nextElementSibling;
                    if (result.success) {
                        formResponse.innerHTML = `<p class="success">${result.message}</p>`;
                        if (action === 'generate-report' && result.data.report_url) {
                            formResponse.innerHTML += `<p><a href="${result.data.report_url}" class="nav-link">Download Report</a></p>`;
                        }
                        if (action === 'login' && result.data.redirect_url) {
                            // Redirect after a short delay to show the success message
                            setTimeout(() => {
                                window.location.href = result.data.redirect_url;
                            }, 1500);
                        } else {
                            // Optionally reset the form for non-login actions
                            form.reset();
                        }
                    } else {
                        formResponse.innerHTML = `<p class="error">${result.message}</p>`;
                    }
                    loadingIndicator ? loadingIndicator.style.display = 'none' : null;
                } catch (error) {
                    console.error('Error submitting form:', error);
                    form.nextElementSibling.innerHTML = '<p class="error">Error submitting form. Please try again later.</p>';
                    loadingIndicator ? loadingIndicator.style.display = 'none' : null;
                }
            });
        });
    }

    // Handle clicks on navigation (only in index.php)
    if (document.querySelectorAll('.nav-link').length > 0) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const mainHref = link.getAttribute('data-href');
                const sidebarHref = link.getAttribute('data-sidebar');
                console.log(`Nav link clicked: Main URL=${mainHref}, Sidebar URL=${sidebarHref}`); // Debugging
                if (mainHref) {
                    loadContent(mainHref, mainContent);
                }
                if (sidebarHref) {
                    // Show sidebar and toggle button for non-"Home" tabs
                    console.log('Showing sidebar and loading content...'); // Debugging
                    sidebar.classList.remove('hidden');
                    container.classList.remove('no-sidebar');
                    toggleSidebarBtn.style.display = 'inline-block';
                    loadContent(sidebarHref, sidebar, true); // Load into the entire sidebar
                } else {
                    // Hide sidebar and toggle button for "Home" tab
                    console.log('Hiding sidebar for Home tab...'); // Debugging
                    sidebar.classList.add('hidden');
                    container.classList.add('no-sidebar');
                    toggleSidebarBtn.style.display = 'none';
                    sidebar.innerHTML = '<h2>Sidebar Placeholder</h2><ul class="sidebar-menu"></ul>'; // Reset sidebar content
                }
            });
        });

        // Handle clicks on sidebar, submenu, and dropdown links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.sidebar-link, .submenu-link, .dropdown-content a');
            if (link) {
                e.preventDefault();
                const href = link.getAttribute('data-href');
                if (href) {
                    console.log(`Sidebar/Submenu/Dropdown link clicked: URL=${href}`); // Debugging
                    loadContent(href, mainContent);
                    if (link.closest('.dropdown-content')) {
                        dropdownContent.style.display = 'none'; // Close dropdown after click
                    }
                }
            }
        });

        // Sidebar toggle functionality
        toggleSidebarBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            toggleSidebarBtn.textContent = sidebar.classList.contains('hidden') ? 'Show Menu' : 'No Menu';
            console.log(`Sidebar toggled: ${sidebar.classList.contains('hidden') ? 'Hidden' : 'Visible'}`); // Debugging
        });

        // Initial load with home.php in main area (no sidebar)
        const initialMainUrl = 'home.php'; // Ensure this file exists
        console.log('Initial load: Loading home.php with no sidebar...'); // Debugging
        container.classList.add('no-sidebar'); // No sidebar on initial load
        toggleSidebarBtn.style.display = 'none'; // Hide toggle button initially
        loadContent(initialMainUrl, mainContent).catch((error) => {
            console.error('Initial load error:', error);
            mainContent.innerHTML = '<p>Error loading home content. Please try again later.</p>';
            loadingIndicator.style.display = 'none';
        });
    }

    // Setup form events on initial load (works for both login.php and index.php)
    setupFormEvents();
});