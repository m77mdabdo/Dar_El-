import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

window.Alpine = Alpine;
Alpine.start();

Chart.register(...registerables);

const djIsArabic = document.documentElement.lang?.startsWith('ar');
Chart.defaults.font.family = djIsArabic
    ? "'Tajawal', -apple-system, BlinkMacSystemFont, sans-serif"
    : "'Poppins', -apple-system, BlinkMacSystemFont, sans-serif";
Chart.defaults.color = '#8a6b70';
Chart.defaults.borderColor = 'rgba(60,11,23,0.08)';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.legend.labels.padding = 16;
Chart.defaults.plugins.tooltip.backgroundColor = '#3C0B17';
Chart.defaults.plugins.tooltip.titleColor = '#E8C39A';
Chart.defaults.plugins.tooltip.bodyColor = '#F7EFE4';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };
Chart.defaults.plugins.tooltip.displayColors = true;
Chart.defaults.plugins.tooltip.boxPadding = 4;

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

/* ===== SHARED TOAST SYSTEM =====
   Exposed on window so any admin bundle (e.g. admin-products.js, loaded
   before this file) can call it from event handlers fired after the page
   has fully loaded — by then window.djToast is guaranteed to exist. */
function djToastStack() {
    let stack = document.getElementById('dj-admin-toast-stack');

    if (! stack) {
        stack = document.createElement('div');
        stack.id = 'dj-admin-toast-stack';
        stack.className = 'dj-admin-toast-stack';
        document.body.appendChild(stack);
    }

    return stack;
}

window.djToast = function (message, type = 'success') {
    if (! message) return;

    const toast = document.createElement('div');
    toast.className = `dj-admin-toast dj-admin-toast-${type}`;
    toast.textContent = message;
    djToastStack().appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('dj-admin-toast-visible'));

    setTimeout(() => {
        toast.classList.remove('dj-admin-toast-visible');
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 4000);
};

/**
 * For JS-driven flows that reload the page on success (e.g. bulk actions),
 * a toast shown right before reload would vanish instantly — this stashes
 * it across the reload so it can be shown once the fresh page loads.
 */
window.djQueueToast = function (message, type = 'success') {
    try {
        sessionStorage.setItem('djPendingToast', JSON.stringify({ message, type }));
    } catch (e) {
        // Storage unavailable (private browsing, quota, etc.) — skip silently.
    }
};

/* ===== PAGE NAVIGATION LOADING BAR =====
   Same purely-visual click acknowledgment as the storefront bundle — see
   app.js for the full rationale. Real navigation is untouched. */
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[href]');
    if (!link || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download')) return;

    const href = link.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) return;

    let url;
    try { url = new URL(href, window.location.href); } catch { return; }
    if (url.origin !== window.location.origin) return;
    if (url.pathname === window.location.pathname && url.search === window.location.search) return;

    document.getElementById('dj-nav-progress')?.classList.add('dj-active');
});

document.addEventListener('submit', (e) => {
    if (e.defaultPrevented || e.target.target) return;
    document.getElementById('dj-nav-progress')?.classList.add('dj-active');
});

window.addEventListener('pageshow', () => {
    document.getElementById('dj-nav-progress')?.classList.remove('dj-active');
});

document.addEventListener('DOMContentLoaded', () => {
    initAdminCharts();

    // Converts the server-flashed status/error session message into a
    // floating toast instead of a permanent inline banner.
    document.querySelectorAll('[data-flash-toast]').forEach((el) => {
        window.djToast(el.textContent.trim(), el.dataset.flashToast);
        el.remove();
    });

    try {
        const pending = sessionStorage.getItem('djPendingToast');
        if (pending) {
            sessionStorage.removeItem('djPendingToast');
            const { message, type } = JSON.parse(pending);
            window.djToast(message, type);
        }
    } catch (e) {
        // Ignore malformed/unavailable storage.
    }
});
