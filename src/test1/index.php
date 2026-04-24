<body>
    <div id="app-root" 
         data-spp-component="main" 
         data-spp-type="ux" 
         data-spp-path="/school1/src/test1/comp/main.js">
    </div>
    
    <script type="text/javascript">
        // Minimal Bridge for Standalone SPP-UX Apps
        if (typeof window.spp_admin === 'undefined') {
            window.spp_admin = {
                api: async (action, data) => {
                    const res = await fetch('/school1/api/' + action, {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    return res.json();
                },
                callAppService: async (name, params) => {
                    const res = await fetch('?__svc=' + name, {
                        method: 'POST',
                        body: JSON.stringify(params)
                    });
                    return res.json();
                }
            };
        }
    </script>
</body>
