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
    const adminToken = new URLSearchParams(window.location.search).get('admin_token') || '';

    if (!missionsBody || !bookingsBody) {
        return;
    }

    function statusPill(value) {
        const normalized = String(value || '').replace('_', '-');
        return `<span class="status-pill status-${escapeHtml(normalized)}">${escapeHtml(value || 'unknown')}</span>`;
    }

    function adminUrl(path) {
        if (!adminToken) {
            return path;
        }

        const [base, queryString = ''] = path.split('?');
        const params = new URLSearchParams(queryString);
        params.set('admin_token', adminToken);
        return `${base}?${params.toString()}`;
    }

    function renderAnalytics(summary) {
        const cards = [
            ['Accepting', summary.accepting_missions || 0, 'success'],
            ['Full', summary.full_missions || 0, 'warning'],
            ['Completed', summary.completed_missions || summary.completed || 0, 'muted']
        ];

        analyticsCards.innerHTML = cards.map(([label, value, variant]) => `
            <div class="analytics-card">
                <strong>${escapeHtml(value)}</strong>
                <span class="summary-chip summary-${escapeHtml(variant)}">${escapeHtml(label)}</span>
            </div>
        `).join('');
    }

    function renderMissionRow(mission) {
        const totalSlots = Number(mission.total_slots || 0);
        const availableSlots = Number(mission.available_slots || 0);
        const percent = totalSlots > 0 ? Math.max(0, Math.min(100, (availableSlots / totalSlots) * 100)) : 0;
        return `
            <tr>
                <td>
                    <strong>${escapeHtml(mission.mission_name)}</strong>
                    <span class="table-subtext">${escapeHtml(mission.organizer_name)}</span>
                </td>
                <td>
                    ${escapeHtml(mission.mission_date_short)}
                    <span class="table-subtext">${escapeHtml(mission.start_time || '')}${mission.end_time ? ` - ${escapeHtml(mission.end_time)}` : ''}</span>
                </td>
                <td>${escapeHtml(mission.city_area || mission.location)}</td>
                <td>
                    <span class="slot-count">${escapeHtml(mission.available_slots)} <span>/ ${escapeHtml(mission.total_slots)}</span></span>
                    <div class="admin-slot-meter"><span style="width:${percent}%"></span></div>
                </td>
                <td>${statusPill(mission.mission_status)}</td>
                <td class="table-actions">
                    <a href="../pages/mission_details.php?id=${encodeURIComponent(mission.mission_id)}" class="btn-secondary compact-button">View</a>
                    <a href="edit_mission.php?id=${encodeURIComponent(mission.mission_id)}" class="btn-secondary compact-button">
                        Edit
                    </a>
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
            const payload = await fetchJson(adminUrl(`../api/admin_dashboard.php${query ? `?${query}` : ''}`));
            const missions = payload.data.missions || [];
            const bookings = payload.data.bookings || [];
            const patients = payload.data.patients || [];
            const pages = payload.data.content_pages || [];

            renderAnalytics(payload.data.summary || {});
            const missionTotal = document.getElementById('adminMissionTotal');
            if (missionTotal) {
                missionTotal.textContent = missions.length;
            }
            syncMissionFilter(missions);
            missionsBody.innerHTML = missions.map(renderMissionRow).join('');
            bookingsBody.innerHTML = bookings.map(renderBookingRow).join('');
            patientsBody.innerHTML = patients.map(renderPatientRow).join('');
            contentPages.innerHTML = pages.map(renderContentPage).join('');

            missionsEmpty.hidden = missions.length > 0;
            bookingsEmpty.hidden = bookings.length > 0;
            patientsEmpty.hidden = patients.length > 0;

            setStatus(status, message || '');
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
            const payload = await fetchJson(adminUrl(`../api/delete_mission.php?id=${encodeURIComponent(button.dataset.id)}`), {
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
                const payload = await fetchJson(adminUrl('../api/pre_screening.php'), {
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
            const payload = await fetchJson(adminUrl('../api/update_booking_status.php'), {
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
            const payload = await fetchJson(adminUrl('../api/content_pages.php'), {
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
