(function () {
    const { fetchJson } = window.KitaKitsApi;

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-cancel-booking]');

        if (!button || !confirm('Cancel this pending booking request?')) {
            return;
        }

        button.disabled = true;

        try {
            await fetchJson(`../api/cancel_booking.php?id=${encodeURIComponent(button.dataset.id)}`, {
                method: 'DELETE'
            });
            window.location.reload();
        } catch (error) {
            alert(error.message || 'Unable to cancel this booking.');
            button.disabled = false;
        }
    });
})();
