(() => {
    const root = document.getElementById('swagger-ui');
    if (!root || typeof window.SwaggerUIBundle !== 'function') {
        return;
    }

    const specUrl = root.getAttribute('data-spec-url') || '';
    if (specUrl === '') {
        return;
    }

    window.ui = window.SwaggerUIBundle({
        url: specUrl,
        dom_id: '#swagger-ui',
        presets: [
            window.SwaggerUIBundle.presets.apis,
            window.SwaggerUIStandalonePreset,
        ],
        layout: 'StandaloneLayout',
    });
})();
