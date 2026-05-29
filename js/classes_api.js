/**
 * js/classes_api.js
 * AJAX consumer for api/classes.php
 *
 * Fetches the upcoming class schedule from the REST API and renders
 * a live "Quick Stats" summary bar at the top of the classes page:
 *  - Total upcoming sessions this week
 *  - Breakdown by class type
 *  - Next class starting soonest
 *
 * The main schedule grid is still server-rendered (it contains user-specific
 * enrollment state that the public API doesn't expose). This widget adds
 * live data on top without interfering with any existing functionality.
 */
document.addEventListener('DOMContentLoaded', () => {
    const statsBar = document.getElementById('classes-api-stats');
    if (!statsBar) return; // Guard: only run on the classes page

    const apiUrl = statsBar.getAttribute('data-api-url') || '../api/classes.php';

    function fetchClasses() {
        fetch(apiUrl)
            .then(res => {
                if (!res.ok) throw new Error('API error ' + res.status);
                return res.json();
            })
            .then(json => {
                if (json.status !== 'success' || !Array.isArray(json.data)) return;
                renderStats(json.data);
            })
            .catch(err => console.error('[classes_api.js] Failed to fetch classes:', err));
    }

    /**
     * Renders a live summary bar from the API data.
     */
    function renderStats(sessions) {
        const typeLabels = {
            powerlifting: 'Powerlifting',
            hiit:         'HIIT',
            crossfit:     'CrossFit',
            yoga:         'Yoga',
        };

        const typeColors = {
            powerlifting: '#f5681a',
            hiit:         '#e84040',
            crossfit:     '#c0a030',
            yoga:         '#42a882',
        };

        // Count per type
        const counts = {};
        sessions.forEach(s => {
            const t = s.type || 'other';
            counts[t] = (counts[t] || 0) + 1;
        });

        // Find next upcoming session (sessions are ordered ASC by scheduled_at)
        const next = sessions.length > 0 ? sessions[0] : null;
        let nextHtml = '';
        if (next) {
            const when = formatRelativeTime(next.scheduled_at);
            const color = typeColors[next.type] || '#7a7f91';
            nextHtml = `
                <span class="classes-stat-next">
                    Next: <strong style="color:${color}">${escapeHtml(next.class_name)}</strong>
                    <span class="classes-stat-when">${when}</span>
                </span>`;
        }

        const pills = Object.entries(counts)
            .map(([type, n]) => {
                const color = typeColors[type] || '#7a7f91';
                const label = typeLabels[type] || capitalize(type);
                return `<span class="classes-stat-pill" style="border-color:${color}20;color:${color};">
                    <strong>${n}</strong> ${label}
                </span>`;
            })
            .join('');

        statsBar.innerHTML = `
            <div class="classes-stat-bar">
                <span class="classes-stat-total">
                    <strong>${sessions.length}</strong> Session${sessions.length !== 1 ? 's' : ''} this week
                </span>
                <div class="classes-stat-pills">${pills}</div>
                ${nextHtml}
                <span class="classes-stat-live">
                    <span class="classes-stat-dot"></span> Live
                </span>
            </div>
        `;
    }

    /**
     * Returns a human-readable relative time string for a datetime string.
     * e.g. "in 2 days", "tomorrow at 09:00", "Monday at 15:00"
     */
    function formatRelativeTime(datetimeStr) {
        const now = new Date();
        const target = new Date(datetimeStr.replace(' ', 'T'));
        const diffMs = target - now;
        const diffH = Math.round(diffMs / (1000 * 60 * 60));
        const diffD = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        const timeStr = target.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });

        if (diffH < 1)   return 'starting soon';
        if (diffH < 24)  return `in ${diffH}h at ${timeStr}`;
        if (diffD === 1) return `tomorrow at ${timeStr}`;
        const dayName = target.toLocaleDateString([], { weekday: 'long' });
        return `${dayName} at ${timeStr}`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
    }

    // Initial fetch + refresh every 30 seconds
    fetchClasses();
    setInterval(fetchClasses, 30000);
});
