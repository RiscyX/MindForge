(() => {
    const input = document.getElementById('avatar-file');
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    input.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.files || target.files.length === 0) {
            return;
        }

        const file = target.files[0];
        const reader = new FileReader();
        reader.onload = (loadEvent) => {
            const result = loadEvent.target && typeof loadEvent.target.result === 'string'
                ? loadEvent.target.result
                : '';
            if (result === '') {
                return;
            }

            const preview = document.querySelector('.avatar-preview');
            const placeholder = document.querySelector('.avatar-preview-placeholder');

            if (preview instanceof HTMLImageElement) {
                preview.src = result;
                return;
            }

            if (placeholder instanceof HTMLElement && placeholder.parentNode) {
                const img = document.createElement('img');
                img.src = result;
                img.alt = 'Avatar Preview';
                img.className = 'rounded-circle img-thumbnail shadow-sm avatar-preview';
                img.style.width = '150px';
                img.style.height = '150px';
                img.style.objectFit = 'cover';
                placeholder.parentNode.replaceChild(img, placeholder);
            }
        };
        reader.readAsDataURL(file);
    });
})();
