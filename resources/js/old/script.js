document.addEventListener('DOMContentLoaded', () => {
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
});