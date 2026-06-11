(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const availableContainer = document.getElementById('availableMissions');
    const bookedContainer = document.getElementById('fullyBookedMissions');
    const fullyBookedSection = document.getElementById('fullyBookedSection');
    const emptyState = document.getElementById('missionsEmptyState');
    const loading = document.getElementById('missionsLoading');
    const status = document.getElementById('missionsStatus');
    const filters = document.getElementById('missionFilters');
    const apiUrl = filters?.dataset.apiUrl || 'api/missions.php';
    const pagePrefix = filters?.dataset.pagePrefix ?? 'pages/';
    const assetPrefix = filters?.dataset.assetPrefix || 'assets/';

    function pageUrl(page, id) {
        return `${pagePrefix}${page}.php?id=${encodeURIComponent(id)}`;
    }

    function missionCard(mission, isFullyBooked = false) {
        const totalSlots = Number(mission.total_slots || mission.available_slots || 0);
        const availableSlots = Number(mission.available_slots || 0);
        const percent = totalSlots > 0 ? Math.max(0, Math.min(100, (availableSlots / totalSlots) * 100)) : 0;
        const slotsContent = isFullyBooked
            ? `<span class="status-badge status-full">Fully Booked</span>
               <div class="mission-unavailable">All slots booked</div>
               <div class="mission-card-actions">
                   <a href="${pageUrl('mission_details', mission.mission_id)}" class="btn-secondary compact-button">View Details</a>
               </div>`
            : `<span class="status-badge status-available">Accepting Bookings</span>
               <div class="mission-slots"><strong>${escapeHtml(mission.available_slots)}</strong> slots available</div>
               <div class="slot-meter" aria-hidden="true"><span style="width: ${percent}%"></span></div>
               <div class="mission-card-actions">
                   <a href="${pageUrl('mission_details', mission.mission_id)}" class="btn-secondary compact-button">View Details</a>
                   <a href="${pageUrl('book_slot', mission.mission_id)}" class="btn-book">
                       <img src="${assetPrefix}icons/book.png" alt="" class="btn-icon">
                       Book Slot
                   </a>
               </div>`;

        return `
            <div class="mission-card${isFullyBooked ? ' mission-card-muted' : ''}">
                <div class="mission-card-head">
                    <div>
                        <h3>${escapeHtml(mission.mission_name || mission.organizer_name)}</h3>
                        <div class="mission-organizer">${escapeHtml(mission.organizer_name)}</div>
                    </div>
                </div>
                <div class="mission-date">${escapeHtml(mission.mission_date_long)}</div>
                <div class="mission-location">${escapeHtml(mission.full_address || mission.location)}</div>
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
            const params = filters ? new URLSearchParams(new FormData(filters)) : new URLSearchParams();
            if (params.get('sort') === 'slots') {
                params.set('order', 'desc');
            }

            const query = params.toString();
            const payload = await fetchJson(`${apiUrl}${query ? `?${query}` : ''}`);
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

            setStatus(status, '');
        } catch (error) {
            setStatus(status, error.message || 'Unable to load missions.', 'error');
        } finally {
            setLoading(loading, false);
        }
    }

    if (availableContainer && bookedContainer) {
        if (filters) {
            filters.addEventListener('submit', (event) => {
                event.preventDefault();
                loadMissions();
            });

            let searchTimer;
            filters.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadMissions, 350);
            });

            filters.addEventListener('change', loadMissions);
        }

        loadMissions();
    }
})();
