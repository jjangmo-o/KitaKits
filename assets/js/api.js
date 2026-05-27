(function () {
    async function fetchJson(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                'Accept': 'application/json',
                ...(options.headers || {})
            }
        });

        let payload;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('The server returned an unreadable response.');
        }

        if (!response.ok || !payload.ok) {
            const message = payload && payload.message
                ? payload.message
                : `Request failed with status ${response.status}.`;
            throw new Error(message);
        }

        return payload;
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setStatus(element, message, type = 'info') {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.className = `status-message status-${type}`;
        element.hidden = message === '';
    }

    function setLoading(element, isLoading, message = 'Loading...') {
        if (!element) {
            return;
        }

        element.hidden = !isLoading;
        element.innerHTML = isLoading
            ? `<span class="spinner" aria-hidden="true"></span><span>${escapeHtml(message)}</span>`
            : '';
    }

    window.KitaKitsApi = {
        fetchJson,
        escapeHtml,
        setStatus,
        setLoading
    };
})();