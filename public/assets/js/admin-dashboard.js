(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const missionsBody = document.getElementById('adminMissionsBody');
    const bookingsBody = document.getElementById('adminBookingsBody');
    const missionsEmpty = document.getElementById('adminMissionsEmpty');
    const bookingsEmpty = document.getElementById('adminBookingsEmpty');
    const loading = document.getElementById('adminLoading');
    const status = document.getElementById('adminStatus');

    if (!missionsBody || !bookingsBody) {
        return;
    }

    function renderMissionRow(mission) {
        return `
            <tr>
                <td>${escapeHtml(mission.mission_id)}</td>
                <td>${escapeHtml(mission.organizer_name)}</td>
                <td>${escapeHtml(mission.mission_date_short)}</td>
                <td>${escapeHtml(mission.location)}</td>
                <td><strong>${escapeHtml(mission.available_slots)}</strong></td>
                <td>${escapeHtml(mission.total_bookings)}</td>
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

    function renderBookingRow(booking) {
        return `
            <tr>
                <td>${escapeHtml(booking.booking_id)}</td>
                <td>${escapeHtml(booking.patient_name)}</td>
                <td>${escapeHtml(booking.contact_number)}</td>
                <td>${escapeHtml(booking.organizer_name)}</td>
                <td>${escapeHtml(booking.mission_date_short)}</td>
            </tr>
        `;
    }

    async function loadDashboard(message = '') {
        setStatus(status, '');
        setLoading(loading, true, 'Loading admin tables...');

        try {
            const payload = await fetchJson('../api/admin_dashboard.php');
            const missions = payload.data.missions || [];
            const bookings = payload.data.bookings || [];

            missionsBody.innerHTML = missions.map(renderMissionRow).join('');
            bookingsBody.innerHTML = bookings.map(renderBookingRow).join('');

            missionsEmpty.hidden = missions.length > 0;
            bookingsEmpty.hidden = bookings.length > 0;

            if (message) {
                setStatus(status, message, 'success');
            } else {
                setStatus(status, `Loaded ${missions.length} mission record(s) and ${bookings.length} booking record(s).`, 'success');
            }
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

        if (!confirm('Are you sure you want to delete this mission? All related bookings will be removed.')) {
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

    loadDashboard();
})();
