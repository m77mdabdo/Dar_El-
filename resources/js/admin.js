import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

window.Alpine = Alpine;
Alpine.start();

Chart.register(...registerables);
Chart.defaults.font.family = "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif";
Chart.defaults.color = '#78716c';

/* =========================================================
   Admin dashboard behavior — kept separate from the storefront
   bundle (app.js) so the admin panel never ships cart/wishlist/
   modal JS it doesn't use, and the storefront never ships Chart.js.
   ========================================================= */

function adminCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function adminFetch(url, method = 'POST') {
    const response = await fetch(url, {
        method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': adminCsrfToken(),
        },
    });

    return response.json().catch(() => ({}));
}

/* ===== CHARTS =====
   Declarative convention: any <canvas class="dj-admin-chart" data-config='{...}'>
   is auto-initialized on load. Future phases (Reports, etc.) can reuse this
   exact pattern with zero new JS. */
function initAdminCharts() {
    document.querySelectorAll('canvas.dj-admin-chart').forEach((canvas) => {
        if (canvas.dataset.djInit) return;
        canvas.dataset.djInit = '1';

        try {
            const config = JSON.parse(canvas.dataset.config);
            new Chart(canvas.getContext('2d'), config);
        } catch (e) {
            console.error('Failed to init chart', canvas, e);
        }
    });
}

/* ===== NOTIFICATION BELL / DROPDOWN ===== */
function updateNotificationBadge(count) {
    document.querySelectorAll('.dj-admin-notif-badge').forEach((badge) => {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    });
}

window.adminMarkNotificationRead = async function (button, notificationId, url) {
    const item = button.closest('[data-notification-item]');
    try {
        const data = await adminFetch(url, 'PATCH');
        item?.classList.remove('dj-admin-notif-unread');
        button.remove();
        if (typeof data.unread_count === 'number') updateNotificationBadge(data.unread_count);
    } catch (e) {
        console.error(e);
    }
};

window.adminMarkAllNotificationsRead = async function (url) {
    try {
        const data = await adminFetch(url, 'PATCH');
        document.querySelectorAll('.dj-admin-notif-unread').forEach((item) => {
            item.classList.remove('dj-admin-notif-unread');
            item.querySelector('[data-mark-read-btn]')?.remove();
        });
        updateNotificationBadge(data.unread_count ?? 0);
    } catch (e) {
        console.error(e);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    initAdminCharts();
});
