{{-- Inline (not resources/css/app.css or resources/js/app.js) so this ships
     the moment this file reaches production via a plain git pull — this
     deploy process has repeatedly been observed running for a while on a
     compiled asset bundle that predates the current commit, with no
     npm run build in between (see the WhatsApp button and shop size-filter
     fixes earlier this project). --}}
<style>
    .dj-search { position: relative; }
    .dj-search-toggle {
        display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%;
        background: transparent; color: var(--dj-gold); border: 1px solid rgba(232,195,154,0.4); transition: .2s;
        flex-shrink: 0;
    }
    .dj-search-toggle svg { width: 18px; height: 18px; }
    .dj-search-toggle:hover { background: rgba(232,195,154,0.1); }

    /* position: fixed (not absolute) + top/left/right set by JS from the
       toggle button's live getBoundingClientRect(). The panel is moved to a
       direct child of <body> at init time (see script below) specifically
       because .dj-nav has backdrop-filter, which — per spec — creates a new
       containing block for fixed-position descendants, same as transform or
       filter. Left nested inside .dj-nav, "inset: 0" below would resolve
       against the ~68px-tall nav bar instead of the viewport (confirmed via
       Puppeteer: measured panel height was 68px, not the screen height). */
    .dj-search-panel {
        position: fixed; width: min(360px, 92vw);
        background: #fff; border-radius: 16px; box-shadow: var(--dj-shadow); padding: 14px;
        z-index: 110; opacity: 0; visibility: hidden; transform: translateY(-8px); transition: opacity .2s, transform .2s;
    }
    .dj-search-panel.dj-open { opacity: 1; visibility: visible; transform: translateY(0); }

    .dj-search-input-wrap { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
    .dj-search-input {
        flex: 1; padding: 12px 14px; border: 1.5px solid var(--dj-cream-2); border-radius: 12px; font-family: inherit;
        font-size: 14px; background: var(--dj-cream); color: var(--dj-ink); min-width: 0;
    }
    .dj-search-input:focus { outline: none; border-color: var(--dj-maroon); }
    .dj-search-close {
        background: transparent; color: var(--dj-rose-dust); font-size: 18px; min-width: 36px; min-height: 36px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }

    .dj-search-results { max-height: min(60vh, 420px); overflow-y: auto; }
    .dj-search-result {
        display: flex; align-items: center; gap: 10px; padding: 8px; border-radius: 12px; transition: background .15s;
        text-decoration: none;
    }
    .dj-search-result:hover { background: var(--dj-cream); }
    .dj-search-result-img {
        width: 48px; height: 48px; border-radius: 10px; object-fit: cover; flex-shrink: 0; background: var(--dj-cream-2);
    }
    .dj-search-result-name { font-size: 13.5px; color: var(--dj-maroon); font-weight: 600; }
    .dj-search-result-price { font-size: 12px; color: var(--dj-rose-dust); margin-top: 2px; }
    .dj-search-see-all {
        display: block; text-align: center; margin-top: 8px; padding: 11px; border-radius: 12px;
        background: var(--dj-cream-2); color: var(--dj-maroon); font-size: 13px; font-weight: 600; text-decoration: none;
        transition: .2s;
    }
    .dj-search-see-all:hover { background: var(--dj-maroon); color: var(--dj-gold); }
    .dj-search-empty, .dj-search-loading { text-align: center; padding: 26px 10px; color: #8a6b70; font-size: 13.5px; }

    @media (max-width: 640px) {
        .dj-search-panel {
            position: fixed; inset: 0; width: 100%; border-radius: 0; transform: none; padding: 16px;
            display: flex; flex-direction: column;
        }
        .dj-search-panel.dj-open { transform: none; }
        .dj-search-results { flex: 1; max-height: none; }
    }
</style>

<div class="dj-search" id="dj-search">
    <button type="button" class="dj-search-toggle" id="dj-search-toggle" aria-label="{{ __('Search') }}" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>

    <div class="dj-search-panel" id="dj-search-panel" role="search">
        <div class="dj-search-input-wrap">
            <input type="text" id="dj-search-input" class="dj-search-input" placeholder="{{ __('Search products...') }}" autocomplete="off">
            <button type="button" class="dj-search-close" id="dj-search-close" aria-label="{{ __('Close') }}">✕</button>
        </div>
        <div id="dj-search-results" class="dj-search-results"></div>
    </div>
</div>

<script>
(function () {
    var i18n = {
        empty: @json(__('No matching results found.')),
        loading: @json(__('Searching...')),
        seeAllTemplate: @json(__('See all results for ":query"')),
    };
    var liveSearchUrl = @json(route('search.live'));

    var root = document.getElementById('dj-search');
    var toggle = document.getElementById('dj-search-toggle');
    var panel = document.getElementById('dj-search-panel');
    var input = document.getElementById('dj-search-input');
    var closeBtn = document.getElementById('dj-search-close');
    var resultsEl = document.getElementById('dj-search-results');

    if (!root || !toggle || !panel || !input || !resultsEl) return;

    // Move the panel out from under .dj-nav (see the CSS comment above) so
    // its position: fixed is contained by the viewport, not the nav bar.
    document.body.appendChild(panel);

    function positionPanel() {
        if (window.innerWidth <= 640) {
            // Mobile: full-screen takeover is handled entirely by the
            // stylesheet's max-width:640px rule — clear any inline values
            // from a previous desktop-width placement so that rule applies.
            panel.style.top = '';
            panel.style.left = '';
            panel.style.right = '';
            return;
        }

        var rect = toggle.getBoundingClientRect();
        panel.style.top = (rect.bottom + 12) + 'px';

        if (document.body.classList.contains('dj-en')) {
            panel.style.left = rect.left + 'px';
            panel.style.right = 'auto';
        } else {
            panel.style.right = (window.innerWidth - rect.right) + 'px';
            panel.style.left = 'auto';
        }
    }

    function esc(str) {
        var div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function openSearch() {
        positionPanel();
        panel.classList.add('dj-open');
        toggle.setAttribute('aria-expanded', 'true');
        setTimeout(function () { input.focus(); }, 50);
    }

    function closeSearch() {
        panel.classList.remove('dj-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    window.addEventListener('resize', function () {
        if (panel.classList.contains('dj-open')) positionPanel();
    });

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        panel.classList.contains('dj-open') ? closeSearch() : openSearch();
    });
    closeBtn.addEventListener('click', closeSearch);

    document.addEventListener('click', function (e) {
        // Panel now lives outside `root` (moved to document.body above), so
        // both trees have to be checked for "was this click inside search".
        if (panel.classList.contains('dj-open') && !root.contains(e.target) && !panel.contains(e.target)) {
            closeSearch();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('dj-open')) closeSearch();
    });

    var debounceTimer = null;
    var abortController = null;

    input.addEventListener('input', function () {
        var term = input.value.trim();
        clearTimeout(debounceTimer);

        if (term.length < 2) {
            resultsEl.innerHTML = '';
            if (abortController) abortController.abort();
            return;
        }

        debounceTimer = setTimeout(function () { runSearch(term); }, 300);
    });

    function runSearch(term) {
        if (abortController) abortController.abort();
        abortController = new AbortController();

        resultsEl.innerHTML = '<div class="dj-search-loading">' + esc(i18n.loading) + '</div>';

        fetch(liveSearchUrl + '?q=' + encodeURIComponent(term), {
            signal: abortController.signal,
            headers: { 'Accept': 'application/json' },
        })
            .then(function (res) { return res.json(); })
            .then(function (data) { renderResults(data); })
            .catch(function (err) {
                if (err.name !== 'AbortError') resultsEl.innerHTML = '';
            });
    }

    function renderResults(data) {
        if (!data.results || data.results.length === 0) {
            resultsEl.innerHTML = '<div class="dj-search-empty">' + esc(i18n.empty) + '</div>';
            return;
        }

        var html = data.results.map(function (p) {
            var img = p.image ? esc(p.image) : '';
            return (
                '<a class="dj-search-result" href="' + esc(p.url) + '">' +
                    (img ? '<img class="dj-search-result-img" src="' + img + '" alt="">' : '<div class="dj-search-result-img"></div>') +
                    '<div>' +
                        '<div class="dj-search-result-name">' + esc(p.name) + '</div>' +
                        '<div class="dj-search-result-price">' + esc(p.price_formatted) + '</div>' +
                    '</div>' +
                '</a>'
            );
        }).join('');

        if (data.see_all_url) {
            var label = i18n.seeAllTemplate.replace(':query', data.query || '');
            html += '<a class="dj-search-see-all" href="' + esc(data.see_all_url) + '">' + esc(label) + '</a>';
        }

        resultsEl.innerHTML = html;
    }
})();
</script>
