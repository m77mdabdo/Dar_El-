import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

/* =========================================================
   Dar El-Jamila storefront behavior
   Plain vanilla JS (mirrors the approved design prototype),
   wired to real fetch() calls against the existing cart routes
   instead of an in-memory fake cart.
   ========================================================= */

function djCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function djFetch(url, method, body) {
    const options = {
        method,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': djCsrfToken(),
        },
    };

    if (body !== undefined) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    // Auth-guarded routes redirect (not a JSON 401) outside of api/* — fetch()
    // follows that redirect transparently, landing on the login page with a
    // 200 status. Detect that case explicitly so callers can prompt login
    // instead of silently treating it as a successful, empty response.
    if (response.redirected && new URL(response.url).pathname === '/login') {
        const error = new Error('Unauthenticated.');
        error.status = 401;
        error.data = {};
        throw error;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(data.message || 'Request failed');
        error.data = data;
        error.status = response.status;
        throw error;
    }

    return data;
}
window.djFetch = djFetch;

function djUpdateCartFromResponse(data) {
    const countEl = document.getElementById('dj-cart-count');
    const totalEl = document.getElementById('dj-cart-total');
    const itemsEl = document.getElementById('dj-drawer-items');

    if (countEl) countEl.textContent = data.count ?? 0;
    if (totalEl && data.total_formatted) totalEl.textContent = data.total_formatted;
    if (itemsEl && typeof data.html === 'string') itemsEl.innerHTML = data.html;

    const checkoutBtn = document.querySelector('.dj-drawer-checkout-btn');
    if (checkoutBtn) {
        const hasIssue = itemsEl?.querySelector('.dj-ci-warning') !== null;
        checkoutBtn.classList.toggle('dj-disabled', hasIssue);
        if (hasIssue) {
            checkoutBtn.setAttribute('aria-disabled', 'true');
        } else {
            checkoutBtn.removeAttribute('aria-disabled');
        }
    }
}

window.djOpenCart = function () {
    document.getElementById('dj-drawer')?.classList.add('dj-show');
    document.getElementById('dj-overlay')?.classList.add('dj-show');
};
window.djCloseCart = function () {
    document.getElementById('dj-drawer')?.classList.remove('dj-show');
    document.getElementById('dj-overlay')?.classList.remove('dj-show');
};

window.djShowToast = function (message) {
    const t = document.getElementById('dj-toast');
    if (!t) return;
    t.textContent = message;
    t.classList.add('dj-show');
    clearTimeout(t._djTimeout);
    t._djTimeout = setTimeout(() => t.classList.remove('dj-show'), 1800);
};

function djUpdateWishlistCount(count) {
    const el = document.getElementById('dj-wishlist-count');
    if (el && typeof count === 'number') el.textContent = count;
}

/* ===== WISHLIST ===== */
window.djToggleWishlist = async function (btn, productId) {
    const addUrl = btn.dataset.addUrl;
    const removeUrl = btn.dataset.removeUrl;
    const loginUrl = btn.dataset.loginUrl;
    const addedMessage = btn.dataset.addedMessage;
    const removedMessage = btn.dataset.removedMessage;
    const loginMessage = btn.dataset.loginMessage;
    const isActive = btn.classList.contains('dj-active');

    if (!addUrl && !removeUrl) {
        djShowToast(loginMessage);
        setTimeout(() => { window.location.href = loginUrl; }, 900);
        return;
    }

    btn.disabled = true;
    btn.classList.add('dj-loading');

    try {
        const data = isActive
            ? await djFetch(removeUrl, 'DELETE')
            : await djFetch(addUrl, 'POST');

        btn.classList.toggle('dj-active', !isActive);
        btn.setAttribute('aria-pressed', (!isActive).toString());
        btn.querySelector('svg')?.setAttribute('fill', !isActive ? 'currentColor' : 'none');
        djUpdateWishlistCount(data.count);
        djShowToast(isActive ? removedMessage : addedMessage);

        // keep every card/modal for this product in sync on the current page
        document.querySelectorAll(`[data-wishlist-product="${productId}"]`).forEach(other => {
            if (other !== btn) {
                other.classList.toggle('dj-active', !isActive);
                other.setAttribute('aria-pressed', (!isActive).toString());
                other.querySelector('svg')?.setAttribute('fill', !isActive ? 'currentColor' : 'none');
            }
        });
    } catch (e) {
        if (e.status === 401) {
            djShowToast(loginMessage);
            setTimeout(() => { window.location.href = loginUrl; }, 900);
        } else {
            djShowToast(e.data?.error || 'Could not update wishlist.');
        }
    } finally {
        btn.disabled = false;
        btn.classList.remove('dj-loading');
    }
};

window.djAddToCart = async function (addUrl, size, quantity, successMessage, errorMessage) {
    try {
        const data = await djFetch(addUrl, 'POST', { size, quantity: quantity || 1 });
        djUpdateCartFromResponse(data);
        djShowToast(successMessage);
        return true;
    } catch (e) {
        djShowToast(e.data?.error || errorMessage);
        return false;
    }
};

window.djChangeCartQty = async function (updateUrlBase, key, newQty) {
    try {
        const data = await djFetch(`${updateUrlBase}/${key}`, 'PATCH', { quantity: newQty });
        djUpdateCartFromResponse(data);
    } catch (e) {
        djShowToast(e.data?.error || 'Could not update quantity.');
    }
};

window.djRemoveFromCart = async function (removeUrlBase, key) {
    try {
        const data = await djFetch(`${removeUrlBase}/${key}`, 'DELETE');
        djUpdateCartFromResponse(data);
    } catch (e) {
        djShowToast(e.data?.error || 'Could not remove item.');
    }
};

/* ===== PRODUCT QUICK-VIEW MODAL ===== */
let djModalProduct = null;
let djModalSize = null;
let djModalQty = 1;

window.djOpenProductModal = function (product) {
    djModalProduct = product;
    djModalQty = 1;
    djModalSize = product.sizes.find(s => s.stock > 0)?.size ?? product.sizes[0]?.size ?? null;
    djRenderProductModal();
    document.getElementById('dj-modal-overlay')?.classList.add('dj-show');
    document.getElementById('dj-overlay')?.classList.add('dj-show');
};

window.djSelectModalSize = function (size) {
    djModalSize = size;
    const stock = djModalProduct?.sizes.find(s => s.size === size)?.stock ?? 0;
    djModalQty = Math.max(1, Math.min(djModalQty, stock || 1));
    djRenderProductModal();
};

window.djChangeModalQty = function (delta) {
    const stock = djModalProduct?.sizes.find(s => s.size === djModalSize)?.stock ?? 1;
    djModalQty = Math.max(1, Math.min(stock, djModalQty + delta));
    djRenderProductModal();
};

window.djConfirmModalAdd = async function () {
    if (!djModalProduct || !djModalSize) return;
    const ok = await djAddToCart(djModalProduct.addUrl, djModalSize, djModalQty, djModalProduct.addedMessage, djModalProduct.errorMessage);
    if (ok) djCloseModal();
};

function djStockStatusLabel(stock, p) {
    const threshold = p.lowStockThreshold || 5;
    if (stock <= 0) return p.outOfStockLabel;
    if (stock <= threshold) return (p.lowStockLabel || 'Only :count left').replace(':count', stock);
    return p.inStockLabel || 'In Stock';
}

function djStockStatusClass(stock, p) {
    const threshold = p.lowStockThreshold || 5;
    if (stock <= 0) return 'dj-out-of-stock';
    if (stock <= threshold) return 'dj-low-stock';
    return 'dj-in-stock';
}

function djWishlistHeartHtml(p) {
    if (!p.wishlistLoginUrl) return '';
    const active = p.inWishlist ? 'dj-active' : '';
    const fill = p.inWishlist ? 'currentColor' : 'none';
    return `
        <button type="button" class="dj-wishlist-btn dj-wishlist-btn-modal ${active}" data-wishlist-product="${p.id}"
            aria-label="${p.wishlistLoginMessage ? 'Wishlist' : 'Wishlist'}" aria-pressed="${p.inWishlist ? 'true' : 'false'}"
            onclick="djToggleWishlist(this, ${p.id})"
            data-add-url="${p.wishlistAddUrl || ''}" data-remove-url="${p.wishlistRemoveUrl || ''}"
            data-login-url="${p.wishlistLoginUrl}" data-added-message="${p.wishlistAddedMessage}"
            data-removed-message="${p.wishlistRemovedMessage}" data-login-message="${p.wishlistLoginMessage}">
            <svg viewBox="0 0 24 24" fill="${fill}" stroke="currentColor" stroke-width="1.8"><path d="M12 20.5s-7.5-4.6-10-9.3C.4 8 1.8 4.5 5 3.6c2-.5 4 .3 5.3 2C11.6 3.9 13.6 3 15.7 3.6c3.1.9 4.5 4.4 3 7.6-2.5 4.7-10 9.3-10 9.3Z"/></svg>
        </button>
    `;
}

function djRenderProductModal() {
    const mount = document.getElementById('dj-modal-mount');
    if (!mount || !djModalProduct) return;
    mount.classList.remove('dj-article');

    const p = djModalProduct;
    const stock = p.sizes.find(s => s.size === djModalSize)?.stock ?? 0;
    const sizesHtml = p.sizes.map(s => `
        <div class="dj-size-opt ${s.size === djModalSize ? 'dj-active' : ''} ${s.stock <= 0 ? 'dj-disabled' : ''}"
             onclick="${s.stock > 0 ? `djSelectModalSize('${s.size}')` : ''}">${s.size}</div>
    `).join('');

    mount.innerHTML = `
        <button class="dj-modal-close" onclick="djCloseModal()" aria-label="${(window.djI18n && window.djI18n.close) || 'Close'}">&times;</button>
        <div class="dj-modal-image dj-photo-wrap dj-tint-maroon"><img src="${p.image}" alt="${p.name}"></div>
        <div class="dj-modal-info">
            ${djWishlistHeartHtml(p)}
            <h2>${p.name}</h2>
            ${p.rating ? `<div class="dj-rating">★★★★★ <span class="dj-rn">${p.rating} · ${p.ratingLabel}</span></div>` : ''}
            <div class="dj-price" style="margin-top:8px;">${p.priceFormatted}</div>
            <div class="dj-desc">${p.description}</div>
            <div class="dj-sizes">${sizesHtml}</div>
            <div class="dj-stock-badge ${djStockStatusClass(stock, p)}">${djStockStatusLabel(stock, p)}</div>
            <div class="dj-qty-select">
                <span style="font-size:12.5px; color:#8a6b70;">${p.qtyLabel}</span>
                <button onclick="djChangeModalQty(-1)" ${djModalQty <= 1 ? 'disabled' : ''}>-</button><span>${djModalQty}</span><button onclick="djChangeModalQty(1)" ${djModalQty >= stock ? 'disabled' : ''}>+</button>
            </div>
            <button class="dj-modal-add" ${stock <= 0 ? 'disabled' : ''} onclick="djConfirmModalAdd()">${stock <= 0 ? p.outOfStockLabel : p.addToCartLabel}</button>
            <div class="dj-modal-trust">
                <span>${p.trust1}</span><span>${p.trust2}</span><span>${p.trust3}</span>
            </div>
            <a href="${p.detailsUrl}" style="margin-top:14px; font-size:12px; text-align:center; color:var(--dj-rose-dust); text-decoration:underline;">${p.viewDetailsLabel}</a>
        </div>
    `;
}

window.djOpenArticleModal = function (article) {
    const mount = document.getElementById('dj-modal-mount');
    if (!mount) return;
    mount.classList.add('dj-article');
    mount.innerHTML = `
        <button class="dj-modal-close" onclick="djCloseModal()" aria-label="${(window.djI18n && window.djI18n.close) || 'Close'}">&times;</button>
        <div class="dj-article-cover dj-photo-wrap dj-tint-maroon"><img src="${article.image}" alt=""></div>
        <div class="dj-article-body">
            <div class="dj-blog-date">${article.category} · ${article.date}</div>
            <h2>${article.title}</h2>
            <p>${article.excerpt}</p>
            <a href="${article.url}" class="dj-read-more">${article.readMoreLabel}</a>
        </div>
    `;
    document.getElementById('dj-modal-overlay')?.classList.add('dj-show');
    document.getElementById('dj-overlay')?.classList.add('dj-show');
};

window.djCloseModal = function () {
    document.getElementById('dj-modal-overlay')?.classList.remove('dj-show');
    document.getElementById('dj-modal-mount')?.classList.remove('dj-article');
    document.getElementById('dj-overlay')?.classList.remove('dj-show');
};

/* ===== FAQ ACCORDION ===== */
window.djToggleFaq = function (questionEl) {
    const item = questionEl.closest('.dj-faq-item');
    if (!item) return;
    const answer = item.querySelector('.dj-faq-a');
    const wasOpen = item.classList.contains('dj-open');

    item.parentElement.querySelectorAll('.dj-faq-item.dj-open').forEach(other => {
        other.classList.remove('dj-open');
        other.querySelector('.dj-faq-a').style.maxHeight = null;
    });

    if (!wasOpen) {
        item.classList.add('dj-open');
        answer.style.maxHeight = answer.scrollHeight + 'px';
    }
};

/* ===== CATEGORY CHIP ACTIVE STATE (visual only; navigation is a real link) ===== */
document.addEventListener('click', (e) => {
    const chip = e.target.closest('.dj-chip[data-chip-group]');
    if (chip) {
        chip.parentElement.querySelectorAll('.dj-chip').forEach(c => c.classList.remove('dj-active'));
        chip.classList.add('dj-active');
    }
});

/* ===== PARTICLES ===== */
function djInitParticles() {
    document.querySelectorAll('[data-particles]').forEach(container => {
        if (container.dataset.djInit) return;
        container.dataset.djInit = '1';
        const n = parseInt(container.dataset.particles) || 12;
        for (let i = 0; i < n; i++) {
            const p = document.createElement('div');
            p.className = 'dj-particle';
            const size = 2 + Math.random() * 4;
            p.style.width = size + 'px';
            p.style.height = size + 'px';
            p.style.left = (Math.random() * 100) + '%';
            p.style.setProperty('--dj-drift', (Math.random() * 60 - 30) + 'px');
            p.style.animationDuration = (7 + Math.random() * 8) + 's';
            p.style.animationDelay = (Math.random() * 10) + 's';
            container.appendChild(p);
        }
    });
}

/* ===== REVEAL ON SCROLL ===== */
const djRevealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('dj-in');
            djRevealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.12 });

function djObserveReveals() {
    document.querySelectorAll('.dj-reveal:not(.dj-in)').forEach(el => djRevealObserver.observe(el));
}

/* ===== COUNTER ANIMATION ===== */
const djStatObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const target = parseInt(el.dataset.count);
        const suffix = el.dataset.suffix || '';
        const duration = 1600;
        const start = performance.now();
        function tick(now) {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased).toLocaleString() + suffix;
            if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
        djStatObserver.unobserve(el);
    });
}, { threshold: 0.4 });

/* ===== SPLASH / SCROLL PROGRESS / BACK TO TOP ===== */
window.addEventListener('load', () => {
    setTimeout(() => document.getElementById('dj-splash')?.classList.add('dj-hide'), 1200);
});

window.addEventListener('scroll', () => {
    const scrollTop = window.scrollY;
    const docHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
    const progress = document.getElementById('dj-scroll-progress');
    const backToTop = document.getElementById('dj-back-to-top');
    if (progress) progress.style.width = pct + '%';
    if (backToTop) backToTop.classList.toggle('dj-show', scrollTop > 500);
}, { passive: true });

document.addEventListener('DOMContentLoaded', () => {
    djInitParticles();
    djObserveReveals();
    document.querySelectorAll('.dj-stat-num').forEach(el => djStatObserver.observe(el));
});
