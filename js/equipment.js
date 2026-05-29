document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("equipment-container");
    if (!container) return;

    const statusFilter = container.getAttribute("data-status") || "";
    const categoryFilter = container.getAttribute("data-category") || "";
    const userRole = container.getAttribute("data-user-role") || "";

    function fetchEquipment() {
        let url = '../api/equipment.php?';
        if (statusFilter) url += `status=${encodeURIComponent(statusFilter)}&`;
        if (categoryFilter) url += `category=${encodeURIComponent(categoryFilter)}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                renderSummary(data.summary);
                renderEquipment(data.equipment);
            })
            .catch(err => console.error("Error fetching equipment:", err));
    }

    function renderSummary(summary) {
        document.getElementById("summary-available").textContent = summary.total_available;
        document.getElementById("summary-total").textContent = summary.total_non_retired;
        document.getElementById("summary-unavailable").textContent = summary.total_non_retired - summary.total_available;
    }

    function renderEquipment(items) {
        if (!items || items.length === 0) {
            container.innerHTML = `
              <div class="equipment-empty">
                <svg class="equipment-empty__icon" viewBox="0 0 40 40" fill="none">
                  <rect x="4" y="12" width="32" height="20" rx="2" stroke="currentColor" stroke-width="1.8"/>
                  <path d="M4 18h32" stroke="currentColor" stroke-width="1.8"/>
                  <circle cx="12" cy="25" r="2" fill="currentColor" opacity=".4"/>
                  <circle cx="20" cy="25" r="2" fill="currentColor" opacity=".4"/>
                  <circle cx="28" cy="25" r="2" fill="currentColor" opacity=".4"/>
                </svg>
                No equipment found.
              </div>
            `;
            return;
        }

        const byCategory = {};
        items.forEach(item => {
            if (!byCategory[item.category]) byCategory[item.category] = [];
            byCategory[item.category].push(item);
        });

        let html = '';
        for (const [cat, eqItems] of Object.entries(byCategory)) {
            html += `
              <header class="equipment-section-heading">
                <span class="equipment-section-heading__title">${escapeHtml(cat)}</span>
                <div class="equipment-section-heading__line"></div>
                <span style="font-family:var(--font-ui);font-size:.65rem;letter-spacing:.14em;color:var(--color-muted);white-space:nowrap;">
                  ${eqItems.length} item${eqItems.length !== 1 ? 's' : ''}
                </span>
              </header>
              <div class="equipment-grid" style="margin-bottom:2rem;">
            `;

            eqItems.forEach(eq => {
                let cssClass = 'status--maintenance';
                let label = 'Maintenance';
                if (eq.overall_status === 'available') {
                    cssClass = 'status--available'; label = 'Available';
                } else if (eq.overall_status === 'in_use') {
                    cssClass = 'status--in-use'; label = 'In Use';
                }

                const dateStr = eq.updated_at ? eq.updated_at.split(' ')[0] : '';
                
                let reserveButtonHtml = '';
                if (userRole === 'member') {
                    reserveButtonHtml = `
                      <a href="reserve_equipment.php?equipment_id=${eq.id}" class="btn btn-primary" style="margin-top: 0.6rem; font-size: 0.72rem; padding: 0.45rem 1rem; width: 100%; text-align: center; border-radius: var(--radius-field);">
                        Reserve
                      </a>
                    `;
                }

                html += `
                  <article class="equipment-card">
                    <div class="equipment-card__body">
                      <div class="equipment-card__category">${escapeHtml(eq.category)}</div>
                      <div class="equipment-card__name">${escapeHtml(eq.name)}</div>
                      <div class="equipment-card__quantity">Qty: <strong>${eq.quantity}</strong></div>
                      ${reserveButtonHtml}
                    </div>
                    <div class="equipment-card__footer">
                      <span class="status-badge ${cssClass}">${label}</span>
                      <span style="font-family:var(--font-ui);font-size:.6rem;letter-spacing:.08em;color:var(--color-muted);">
                        ${escapeHtml(dateStr)}
                      </span>
                    </div>
                  </article>
                `;
            });
            html += `</div>`;
        }
        container.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    fetchEquipment();
    setInterval(fetchEquipment, 15000); // Poll every 15 seconds
});
