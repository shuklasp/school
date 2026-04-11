/**
 * SPP Auto-Init - Bootstraps the Single Page Application router
 * 
 * This script is automatically injected to initialize the SPPRouter once
 * all scripts are loaded. It allows "Drop and Play" pages to gain
 * instant SPA routing and validation support.
 */
document.addEventListener("DOMContentLoaded", function() {
    if (typeof SPPRouter !== 'undefined') {
        SPPRouter.init();
        console.log("SPP Router auto-initialized.");
    } else {
        console.warn("SPPRouter not found, check include paths.");
    }
});
