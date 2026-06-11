(function () {
    const { fetchJson, escapeHtml, setStatus, setLoading } = window.KitaKitsApi;

    const availableContainer = document.getElementById('availableMissions');
    const bookedContainer = document.getElementById('fullyBookedMissions');
    const fullyBookedSection = document.getElementById('fullyBookedSection');
    const emptyState = document.getElementById('missionsEmptyState');
    const loading = document.getElementById('missionsLoading');
    const status = document.getElementById('missionsStatus');
    const filters = document.getElementById('missionFilters');
    const countText = document.getElementById('missionsCount');
    const apiUrl = filters?.dataset.apiUrl || 'api/missions.php';
    const pagePrefix = filters?.dataset.pagePrefix ?? 'pages/';
    const assetPrefix = filters?.dataset.assetPrefix || 'assets/';
    const statusInput = filters?.querySelector('input[name="status"]');
    const statusTabs = filters ? Array.from(filters.querySelectorAll('.mission-status-tab')) : [];

    function pageUrl(page, id) {
        return `${pagePrefix}${page}.php?id=${encodeURIComponent(id)}`;
    }

    function missionDateLabel(mission) {
        return mission.mission_time_range
            ? `${mission.mission_date_long} · ${mission.mission_time_range}`
            : mission.mission_date_long;
    }

    function missionDescription(mission) {
        if (mission.venue_name) {
            return mission.venue_name;
        }

        if (mission.organizer_name && mission.organizer_name !== mission.mission_name) {
            return mission.organizer_name;
        }

        return mission.city_area ? `Cataract surgery mission in ${mission.city_area}` : 'Cataract surgery mission';
    }

    function updateCount(availableCount) {
        if (!countText) {
            return;
        }

        countText.textContent = `${availableCount} ${availableCount === 1 ? 'mission' : 'missions'} currently accepting bookings`;
    }

    function missionCard(mission, variant = 'available') {
        const totalSlots = Number(mission.total_slots || mission.available_slots || 0);
        const availableSlots = Number(mission.available_slots || 0);
        const percent = totalSlots > 0 ? Math.max(0, Math.min(100, (availableSlots / totalSlots) * 100)) : 0;
        const isFullyBooked = variant === 'full' || mission.available_slots <= 0 || mission.mission_status === 'closed';
        const isCompleted = variant === 'completed' || mission.mission_status === 'completed';
        const statusClass = isCompleted ? 'status-completed' : (isFullyBooked ? 'status-full' : 'status-available');
        const statusLabel = isCompleted ? 'Completed' : (isFullyBooked ? 'Fully Booked' : 'Accepting Bookings');
        const slotsLabel = isCompleted ? 'Final slots' : (isFullyBooked ? 'Slots booked' : 'Slots remaining');
        const slotCount = totalSlots > 0 ? `${availableSlots} / ${totalSlots}` : `${availableSlots}`;
        const actions = isFullyBooked || isCompleted
            ? `<a href="${pageUrl('mission_details', mission.mission_id)}" class="btn-secondary compact-button">View Details</a>`
            : `<a href="${pageUrl('mission_details', mission.mission_id)}" class="btn-secondary compact-button">View Details</a>
               <a href="${pageUrl('book_slot', mission.mission_id)}" class="btn-book">Book Slot</a>`;

        return `
            <div class="mission-card${isFullyBooked || isCompleted ? ' mission-card-muted' : ''}">
                <div class="mission-card-head">
                    <div>
                        <h3>${escapeHtml(mission.mission_name || mission.organizer_name)}</h3>
                        <p class="mission-organizer">${escapeHtml(missionDescription(mission))}</p>
                    </div>
                    <span class="status-badge ${statusClass}">${statusLabel}</span>
                </div>
                <div class="mission-meta-list">
                    <div class="mission-date">
                        <img src="${assetPrefix}icons/calendar-purple.svg" alt="">
                        <span>${escapeHtml(missionDateLabel(mission))}</span>
                    </div>
                    <div class="mission-location">
                        <img src="${assetPrefix}icons/map-pin.svg" alt="">
                        <span>${escapeHtml(mission.full_address || mission.location)}</span>
                    </div>
                </div>
                <div class="mission-slot-summary">
                    <span>${slotsLabel}</span>
                    <strong>${escapeHtml(slotCount)}</strong>
                </div>
                <div class="slot-meter" aria-hidden="true"><span style="width: ${percent}%"></span></div>
                <div class="mission-card-actions">
                    ${actions}
                </div>
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
            const completed = payload.data.completed || [];
            const allMissions = payload.data.all || [];
            const selectedStatus = params.get('status') || 'available';
            const visibleMissions = selectedStatus === 'full'
                ? fullyBooked
                : (selectedStatus === 'completed' ? completed : (selectedStatus === 'all' ? allMissions : available));

            updateCount(available.length);

            if (visibleMissions.length === 0) {
                emptyState.hidden = false;
                return;
            }

            availableContainer.innerHTML = visibleMissions.map((mission) => {
                const variant = mission.mission_status === 'completed'
                    ? 'completed'
                    : ((mission.available_slots <= 0 || mission.mission_status === 'closed') ? 'full' : 'available');
                return missionCard(mission, variant);
            }).join('');

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

            statusTabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    if (statusInput) {
                        statusInput.value = tab.dataset.status || 'available';
                    }

                    statusTabs.forEach((item) => item.classList.toggle('is-active', item === tab));
                    loadMissions();
                });
            });
        }

        loadMissions();
    }
})();
