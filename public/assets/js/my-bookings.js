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

    function statusClass(value) {
        return `status-${String(value || 'booked').replace('_', '-')}`;
    }

    function bookingCard(booking, contact) {
        const editUrl = `edit_booking.php?id=${encodeURIComponent(booking.booking_id)}&contact=${encodeURIComponent(contact)}`;
        const intakeUrl = `pre_screening.php?id=${encodeURIComponent(booking.booking_id)}&contact=${encodeURIComponent(contact)}`;
        const slipUrl = `booking_slip.php?id=${encodeURIComponent(booking.booking_id)}&contact=${encodeURIComponent(contact)}`;
        const canPrint = booking.booking_status === 'confirmed';
        const canCancel = !['cancelled', 'completed', 'no_show'].includes(booking.booking_status);

        return `
            <div class="booking-card">
                <div class="booking-header">
                    <h3>${escapeHtml(booking.mission_name || booking.organizer_name)}</h3>
                    <span class="booking-id">${escapeHtml(booking.booking_reference || `ID ${booking.booking_id}`)}</span>
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
                        <span class="detail-label">Full Address:</span>
                        <span class="detail-value">${escapeHtml(booking.full_address || booking.location)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact Number:</span>
                        <span class="detail-value">${escapeHtml(booking.contact_number)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Booking Status:</span>
                        <span class="detail-value status-pill ${statusClass(booking.booking_status)}">${escapeHtml(booking.booking_status)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Headcount:</span>
                        <span class="detail-value">${escapeHtml(booking.total_headcount)} total (${escapeHtml(booking.companion_count)} companion/s)</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pre-screening:</span>
                        <span class="detail-value">${escapeHtml(booking.intake_review_status)}</span>
                    </div>
                </div>

                <div class="booking-actions">
                    <a href="${editUrl}" class="btn-edit">Edit</a>
                    <a href="${intakeUrl}" class="btn-secondary compact-button">Pre-screening</a>
                    ${canPrint ? `<a href="${slipUrl}" class="btn-primary compact-button">Print Slip</a>` : `<a href="${slipUrl}" class="btn-secondary compact-button">View Slip Status</a>`}
                    ${canCancel ? `<button
                        type="button"
                        class="btn-delete"
                        data-delete-booking
                        data-id="${escapeHtml(booking.booking_id)}"
                        data-contact="${escapeHtml(contact)}"
                    >Cancel</button>` : ''}
                </div>

                <p class="reminder">
                    ${canPrint
                        ? 'Your slot is secured. Bring your confirmation slip and valid ID on mission day.'
                        : 'Booked means your request is received. The slot is secured only after admin confirmation.'}
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

        if (!confirm('Cancel this booking? If it was confirmed, the slot will be returned to the mission.')) {
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
