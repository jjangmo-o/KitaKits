(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const availableContainer = document.getElementById('availableMissions');
    const bookedContainer = document.getElementById('fullyBookedMissions');
    const fullyBookedSection = document.getElementById('fullyBookedSection');
    const emptyState = document.getElementById('missionsEmptyState');
    const loading = document.getElementById('missionsLoading');
    const status = document.getElementById('missionsStatus');

    function missionCard(mission, isFullyBooked = false) {
        const slotsContent = isFullyBooked
            ? '<div class="mission-unavailable">All slots booked</div>'
            : `<div class="mission-slots"><strong>${escapeHtml(mission.available_slots)}</strong> slots available</div>
               <a href="book_slot.php?id=${encodeURIComponent(mission.mission_id)}" class="btn-book">
                   <img src="assets/icons/book.png" alt="" class="btn-icon">
                   Book Your Slot
               </a>`;

        return `
            <div class="mission-card${isFullyBooked ? ' mission-card-muted' : ''}">
                <h3>${escapeHtml(mission.organizer_name)}</h3>
                <div class="mission-date">Date: ${escapeHtml(mission.mission_date_long)}</div>
                <div class="mission-location">Location: ${escapeHtml(mission.location)}</div>
                ${slotsContent}
            </div>
        `;
    }

    async function loadMissions() {
        setStatus(status, '');
        setLoading(loading, true, 'Loading missions...');
        availableContainer.innerHTML = '';
        bookedContainer.innerHTML = '';
        emptyState.hidden = true;
        fullyBookedSection.hidden = true;

        try {
            const payload = await fetchJson('api/missions.php');
            const available = payload.data.available || [];
            const fullyBooked = payload.data.fully_booked || [];

            if (available.length === 0 && fullyBooked.length === 0) {
                emptyState.hidden = false;
                return;
            }

            availableContainer.innerHTML = available.map((mission) => missionCard(mission)).join('');

            if (fullyBooked.length > 0) {
                fullyBookedSection.hidden = false;
                bookedContainer.innerHTML = fullyBooked
                    .map((mission) => missionCard(mission, true))
                    .join('');
            }

            setStatus(status, `Loaded ${available.length + fullyBooked.length} mission record(s).`, 'success');
        } catch (error) {
            setStatus(status, error.message || 'Unable to load missions.', 'error');
        } finally {
            setLoading(loading, false);
        }
    }

    if (availableContainer && bookedContainer) {
        loadMissions();
    }
})();