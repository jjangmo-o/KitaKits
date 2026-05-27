(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const form = document.getElementById('bookingSearchForm');
    const list = document.getElementById('bookingsResults');
    const tips = document.getElementById('bookingTips');
    const status = document.getElementById('bookingSearchStatus');
    const loading = document.getElementById('bookingSearchLoading');
    const submitButton = document.getElementById('bookingSearchSubmit');

    if (!form || !list) {
        return;
    }

    function bookingCard(booking, contact) {
        const editUrl = `edit_booking.php?id=${encodeURIComponent(booking.booking_id)}&contact=${encodeURIComponent(contact)}`;

        return `
            <div class="booking-card">
                <div class="booking-header">
                    <h3>${escapeHtml(booking.organizer_name)}</h3>
                    <span class="booking-id">ID: ${escapeHtml(booking.booking_id)}</span>
                </div>

                <div class="booking-details">
                    <div class="detail-row">
                        <span class="detail-label">Patient Name:</span>
                        <span class="detail-value">${escapeHtml(booking.patient_name)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Mission Date:</span>
                        <span class="detail-value">${escapeHtml(booking.mission_date_long)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Location:</span>
                        <span class="detail-value">${escapeHtml(booking.location)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact Number:</span>
                        <span class="detail-value">${escapeHtml(booking.contact_number)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value status-confirmed">${escapeHtml(booking.status)}</span>
                    </div>
                </div>

                <div class="booking-actions">
                    <a href="${editUrl}" class="btn-edit">Edit</a>
                    <button
                        type="button"
                        class="btn-delete"
                        data-delete-booking
                        data-id="${escapeHtml(booking.booking_id)}"
                        data-contact="${escapeHtml(contact)}"
                    >Delete</button>
                </div>

                <p class="reminder">
                    Reminder: Please arrive 30 minutes early on the mission date. Bring your ID and any medical documents.
                </p>
            </div>
        `;
    }

    async function loadBookings(contact) {
        setStatus(status, '');
        setLoading(loading, true, 'Searching bookings...');
        submitButton.disabled = true;
        list.innerHTML = '';
        tips.hidden = true;

        try {
            const payload = await fetchJson(`../api/bookings.php?contact=${encodeURIComponent(contact)}`);
            const bookings = payload.data.bookings || [];

            if (bookings.length === 0) {
                setStatus(status, 'No bookings found for this contact number. Please verify and try again, or create a new booking.', 'error');
                return;
            }

            list.innerHTML = bookings.map((booking) => bookingCard(booking, payload.data.contact)).join('');
            tips.hidden = false;
            setStatus(status, `Found ${bookings.length} booking${bookings.length === 1 ? '' : 's'} for you.`, 'success');
        } catch (error) {
            setStatus(status, error.message || 'Unable to search bookings.', 'error');
        } finally {
            setLoading(loading, false);
            submitButton.disabled = false;
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const contact = new FormData(form).get('contact');
        loadBookings(contact);
    });

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-delete-booking]');

        if (!button) {
            return;
        }

        if (!confirm('Cancel this booking? The slot will be returned to the mission.')) {
            return;
        }

        button.disabled = true;

        try {
            const id = button.dataset.id;
            const contact = button.dataset.contact;
            const payload = await fetchJson(`../api/delete_booking.php?id=${encodeURIComponent(id)}&contact=${encodeURIComponent(contact)}`, {
                method: 'DELETE'
            });

            setStatus(status, payload.message, 'success');
            await loadBookings(contact);
        } catch (error) {
            setStatus(status, error.message || 'Unable to cancel booking.', 'error');
            button.disabled = false;
        }
    });

    const initialContact = new URLSearchParams(window.location.search).get('contact');
    if (initialContact) {
        form.contact.value = initialContact;
        loadBookings(initialContact);
    }
})();
