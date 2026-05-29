/**
 * js/trainers.js
 * AJAX consumer for api/trainers.php
 *
 * Fetches the live trainer list from the REST API and:
 *  1. Updates the trainer stats bar (total count, specialties breakdown).
 *  2. Injects specialty badge labels onto each trainer card rendered by PHP,
 *     keeping the data in sync with what the API returns without a full reload.
 */
document.addEventListener('DOMContentLoaded', () => {
    const statsBar = document.getElementById('trainers-api-stats');
    if (!statsBar) return; // Guard: only run on the trainers page

    const apiUrl = statsBar.getAttribute('data-api-url') || '../api/trainers.php';

    function fetchTrainers() {
        fetch(apiUrl)
            .then(res => {
                if (!res.ok) throw new Error('API error ' + res.status);
                return res.json();
            })
            .then(json => {
                if (json.status !== 'success' || !Array.isArray(json.data)) return;
                renderStats(json.data);
                syncBadges(json.data);
            })
            .catch(err => console.error('[trainers.js] Failed to fetch trainers:', err));
    }

    /**
     * Renders a live stats bar: total active trainers + breakdown by specialty.
     */
    function renderStats(trainers) {
        const specialtyLabels = {
            strength:     'Strength',
            cardio:       'HIIT & Cardio',
            crossfit:     'CrossFit',
            yoga:         'Yoga & Recovery',
            pilates:      'Pilates',
            martial_arts: 'Martial Arts',
            nutrition:    'Nutrition',
        };

        // Count per specialty
        const counts = {};
        trainers.forEach(t => {
            const key = t.specialty || 'other';
            counts[key] = (counts[key] || 0) + 1;
        });

        const pills = Object.entries(counts)
            .map(([key, n]) => {
                const label = specialtyLabels[key] || capitalize(key);
                return `<span class="trainer-stat-pill">
                    <strong>${n}</strong> ${label}
                </span>`;
            })
            .join('');

        statsBar.innerHTML = `
            <div class="trainer-stat-bar">
                <span class="trainer-stat-total">
                    <strong>${trainers.length}</strong> Active Trainer${trainers.length !== 1 ? 's' : ''}
                </span>
                <div class="trainer-stat-pills">${pills}</div>
                <span class="trainer-stat-live">
                    <span class="trainer-stat-dot"></span> Live
                </span>
            </div>
        `;
    }

    /**
     * Syncs the years_experience shown on each card from the API response.
     * The PHP-rendered cards have data-trainer-id attributes we can match on.
     */
    function syncBadges(trainers) {
        const byId = {};
        trainers.forEach(t => { byId[t.trainer_id] = t; });

        document.querySelectorAll('[data-trainer-id]').forEach(card => {
            const id = parseInt(card.getAttribute('data-trainer-id'), 10);
            const trainer = byId[id];
            if (!trainer) return;

            // Update experience count if the element exists
            const expEl = card.querySelector('[data-trainer-exp]');
            if (expEl && trainer.years_experience !== undefined) {
                const yrs = parseInt(trainer.years_experience, 10);
                expEl.textContent = `${yrs} yr${yrs !== 1 ? 's' : ''} experience`;
            }

            // Mark card as API-synced
            card.setAttribute('data-api-synced', 'true');
        });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
    }

    // Initial fetch + refresh every 60 seconds (trainers change rarely)
    fetchTrainers();
    setInterval(fetchTrainers, 60000);
});
