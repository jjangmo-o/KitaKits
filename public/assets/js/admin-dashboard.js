(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const analyticsCards = document.getElementById('analyticsCards');
    const missionsBody = document.getElementById('adminMissionsBody');
    const bookingsBody = document.getElementById('adminBookingsBody');
    const patientsBody = document.getElementById('adminPatientsBody');
    const contentPages = document.getElementById('contentPages');
    const missionsEmpty = document.getElementById('adminMissionsEmpty');
    const bookingsEmpty = document.getElementById('adminBookingsEmpty');
    const patientsEmpty = document.getElementById('adminPatientsEmpty');
    const bookingFilters = document.getElementById('adminBookingFilters');
    const bookingMissionFilter = document.getElementById('bookingMissionFilter');
    const loading = document.getElementById('adminLoading');
    const status = document.getElementById('adminStatus');

    if (!missionsBody || !bookingsBody) {
        return;
    }

    function statusPill(value) {
        const normalized = String(value || '').replace('_', '-');
        return `<span class="status-pill status-${escapeHtml(normalized)}">${escapeHtml(value || 'unknown')}</span>`;
    }

    function renderAnalytics(summary) {
        const cards = [
            ['Missions', summary.missions],
            ['Bookings', summary.bookings],
            ['Confirmed Headcount', summary.confirmed_headcount],
            ['Completed Cases', summary.completed],
            ['Patients', summary.patients]
        ];

        analyticsCards.innerHTML = cards.map(([label, value]) => `
            <div class="analytics-card">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>
        `).join('');
    }

    function renderMissionRow(mission) {
        return `
            <tr>
                <td>${escapeHtml(mission.mission_id)}</td>
                <td>
                    <strong>${escapeHtml(mission.mission_name)}</strong>
                    <span class="table-subtext">${escapeHtml(mission.organizer_name)}</span>
                </td>
                <td>${escapeHtml(mission.mission_date_short)}</td>
                <td>${escapeHtml(mission.city_area)}</td>
                <td>${statusPill(mission.mission_status)}</td>
                <td>${escapeHtml(mission.available_slots)} / ${escapeHtml(mission.total_slots)}</td>
                <td>
                    ${escapeHtml(mission.total_bookings)}
                    <span class="table-subtext">${escapeHtml(mission.confirmed_count)} confirmed</span>
                </td>
                <td>${escapeHtml(mission.completion_rate)}%</td>
                <td class="table-actions">
                    <a href="edit_mission.php?id=${encodeURIComponent(mission.mission_id)}" class="btn-edit">
                        <img src="../assets/icons/edit.png" alt="" class="btn-icon">
                        Edit
                    </a>
                    <button type="button" class="btn-delete" data-delete-mission data-id="${escapeHtml(mission.mission_id)}">
                        <img src="../assets/icons/delete.png" alt="" class="btn-icon">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }

    function bookingActions(booking) {
        const id = escapeHtml(booking.booking_id);
        const actions = [];

        if (booking.booking_status === 'booked') {
            actions.push(`<button type="button" class="btn-edit compact-button" data-booking-status="confirmed" data-id="${id}">Confirm</button>`);
            actions.push(`<button type="button" class="btn-delete compact-button" data-booking-status="rejected" data-id="${id}">Reject</button>`);
        }

        if (booking.booking_status === 'confirmed') {
            actions.push(`<button type="button" class="btn-edit compact-button" data-booking-status="completed" data-id="${id}">Complete</button>`);
            actions.push(`<button type="button" class="btn-secondary compact-button" data-booking-status="no_show" data-id="${id}">No-show</button>`);
        }

        if (!['cancelled', 'completed', 'no_show'].includes(booking.booking_status)) {
            actions.push(`<button type="button" class="btn-delete compact-button" data-booking-status="cancelled" data-id="${id}">Cancel</button>`);
        }

        actions.push(`<button type="button" class="btn-secondary compact-button" data-review-intake data-id="${id}">Review Intake</button>`);

        return actions.join('');
    }

    function renderBookingRow(booking) {
        return `
            <tr>
                <td>${escapeHtml(booking.booking_reference)}</td>
                <td>
                    <strong>${escapeHtml(booking.patient_name)}</strong>
                    <span class="table-subtext">Patient #${escapeHtml(booking.patient_id)}</span>
                </td>
                <td>${escapeHtml(booking.contact_number)}</td>
                <td>${escapeHtml(booking.mission_name)}</td>
                <td>${escapeHtml(booking.mission_date_short)}</td>
                <td>${statusPill(booking.booking_status)}</td>
                <td>${escapeHtml(booking.total_headcount)}</td>
                <td>
                    ${statusPill(booking.intake_review_status)}
                    ${booking.contraindication_flags ? `<span class="table-subtext">${escapeHtml(booking.contraindication_flags)}</span>` : ''}
                </td>
                <td class="table-actions">${bookingActions(booking)}</td>
            </tr>
        `;
    }

    function renderPatientRow(patient) {
        return `
            <tr>
                <td>${escapeHtml(patient.patient_id)}</td>
                <td>${escapeHtml(patient.patient_name)}</td>
                <td>${escapeHtml(patient.contact_number)}</td>
                <td>${escapeHtml(patient.email || '-')}</td>
                <td>${escapeHtml(patient.city || '-')}</td>
                <td>${escapeHtml(patient.booking_count)}</td>
            </tr>
        `;
    }

    function renderContentPage(page) {
        return `
            <form class="content-editor" data-content-form>
                <input type="hidden" name="page_id" value="${escapeHtml(page.page_id)}">
                <div class="content-editor-header">
                    <strong>${escapeHtml(page.page_key)}</strong>
                    <select name="status">
                        ${['draft', 'published', 'archived'].map((item) => `
                            <option value="${item}" ${page.status === item ? 'selected' : ''}>${item}</option>
                        `).join('')}
                    </select>
                </div>
                <label>Title</label>
                <input type="text" name="title" value="${escapeHtml(page.title)}" maxlength="150" required>
                <label>Body</label>
                <textarea name="body" rows="4" required>${escapeHtml(page.body)}</textarea>
                <button type="submit" class="btn-primary compact-button">Save Content</button>
            </form>
        `;
    }

    function syncMissionFilter(missions) {
        const selected = bookingMissionFilter.value;
        bookingMissionFilter.innerHTML = '<option value="">All missions</option>' + missions.map((mission) => `
            <option value="${escapeHtml(mission.mission_id)}">${escapeHtml(mission.mission_name)}</option>
        `).join('');
        bookingMissionFilter.value = selected;
    }

    function dashboardQuery() {
        if (!bookingFilters) {
            return '';
        }

        const params = new URLSearchParams(new FormData(bookingFilters));
        for (const [key, value] of Array.from(params.entries())) {
            if (value === '') {
                params.delete(key);
            }
        }
        return params.toString();
    }

    async function loadDashboard(message = '') {
        setStatus(status, '');
        setLoading(loading, true, 'Loading admin dashboard...');

        try {
            const query = dashboardQuery();
            const payload = await fetchJson(`../api/admin_dashboard.php${query ? `?${query}` : ''}`);
            const missions = payload.data.missions || [];
            const bookings = payload.data.bookings || [];
            const patients = payload.data.patients || [];
            const pages = payload.data.content_pages || [];

            renderAnalytics(payload.data.summary || {});
            syncMissionFilter(missions);
            missionsBody.innerHTML = missions.map(renderMissionRow).join('');
            bookingsBody.innerHTML = bookings.map(renderBookingRow).join('');
            patientsBody.innerHTML = patients.map(renderPatientRow).join('');
            contentPages.innerHTML = pages.map(renderContentPage).join('');

            missionsEmpty.hidden = missions.length > 0;
            bookingsEmpty.hidden = bookings.length > 0;
            patientsEmpty.hidden = patients.length > 0;

            setStatus(status, message || `Loaded ${missions.length} mission(s), ${bookings.length} booking(s), and ${patients.length} patient(s).`, 'success');
        } catch (error) {
            setStatus(status, error.message || 'Unable to load admin dashboard.', 'error');
        } finally {
            setLoading(loading, false);
        }
    }

    missionsBody.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-delete-mission]');

        if (!button) {
            return;
        }

        if (!confirm('Delete this mission and all related bookings?')) {
            return;
        }

        button.disabled = true;

        try {
            const payload = await fetchJson(`../api/delete_mission.php?id=${encodeURIComponent(button.dataset.id)}`, {
                method: 'DELETE'
            });
            await loadDashboard(payload.message);
        } catch (error) {
            setStatus(status, error.message || 'Unable to delete mission.', 'error');
            button.disabled = false;
        }
    });

    bookingsBody.addEventListener('click', async (event) => {
        const statusButton = event.target.closest('[data-booking-status]');
        const reviewButton = event.target.closest('[data-review-intake]');

        if (reviewButton) {
            const reviewStatus = prompt('Review status: pending, cleared, flagged, or not_cleared', 'cleared');
            if (!reviewStatus) return;
            const notes = prompt('Coordinator notes', '') || '';
            const body = new FormData();
            body.set('action', 'review');
            body.set('booking_id', reviewButton.dataset.id);
            body.set('review_status', reviewStatus);
            body.set('coordinator_notes', notes);

            try {
                const payload = await fetchJson('../api/pre_screening.php', {
                    method: 'POST',
                    body
                });
                await loadDashboard(payload.message);
            } catch (error) {
                setStatus(status, error.message || 'Unable to update intake review.', 'error');
            }
            return;
        }

        if (!statusButton) {
            return;
        }

        const nextStatus = statusButton.dataset.bookingStatus;
        if (!confirm(`Change this booking to ${nextStatus}?`)) {
            return;
        }

        const body = new FormData();
        body.set('booking_id', statusButton.dataset.id);
        body.set('booking_status', nextStatus);

        statusButton.disabled = true;

        try {
            const payload = await fetchJson('../api/update_booking_status.php', {
                method: 'POST',
                body
            });
            await loadDashboard(payload.message);
        } catch (error) {
            setStatus(status, error.message || 'Unable to update booking status.', 'error');
            statusButton.disabled = false;
        }
    });

    contentPages.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-content-form]');

        if (!form) {
            return;
        }

        event.preventDefault();

        try {
            const payload = await fetchJson('../api/content_pages.php', {
                method: 'POST',
                body: new FormData(form)
            });
            await loadDashboard(payload.message);
        } catch (error) {
            setStatus(status, error.message || 'Unable to save content page.', 'error');
        }
    });

    if (bookingFilters) {
        bookingFilters.addEventListener('submit', (event) => {
            event.preventDefault();
            loadDashboard('Booking filters applied.');
        });
    }

    loadDashboard();
})();
