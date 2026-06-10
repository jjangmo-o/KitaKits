(function () {
    const { fetchJson, setStatus, setLoading, escapeHtml } = window.KitaKitsApi;

    const form = document.getElementById('bookingForm');
    const status = document.getElementById('bookingStatus');
    const loading = document.getElementById('bookingLoading');
    const submitButton = document.getElementById('bookingSubmit');
    const slotsValue = document.getElementById('remainingSlotsValue');
    const successActions = document.getElementById('bookingSuccessActions');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        setStatus(status, '');
        if (successActions) {
            successActions.hidden = true;
            successActions.innerHTML = '';
        }
        setLoading(loading, true, 'Submitting booking...');
        submitButton.disabled = true;

        try {
            const payload = await fetchJson('../api/book_slot.php', {
                method: 'POST',
                body: new FormData(form)
            });

            setStatus(status, payload.message, 'success');

            if (successActions) {
                successActions.innerHTML = `
                    <a href="patient_portal.php?requested=1#portal-bookings" class="btn-primary compact-button">Track in Patient Portal</a>
                `;
                successActions.hidden = false;
            }

            if (slotsValue && payload.data.remaining_slots !== undefined) {
                slotsValue.textContent = payload.data.remaining_slots;
            }

            form.reset();

            if (payload.data.remaining_slots <= 0) {
                form.hidden = true;
                const closed = document.getElementById('bookingClosedMessage');
                if (closed) {
                    closed.hidden = false;
                    closed.textContent = 'This mission is now fully booked.';
                }
            }
        } catch (error) {
            setStatus(status, error.message || 'Unable to submit booking.', 'error');
        } finally {
            setLoading(loading, false);
            submitButton.disabled = false;
        }
    });
})();
