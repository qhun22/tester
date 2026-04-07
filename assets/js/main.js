/**
 * main.js — TrắcNghiệm Online
 * Handles: search/filter on index, sidebar toggle on dashboard, counter animation
 */

document.addEventListener('DOMContentLoaded', function () {
    initSubjectSearch();
    initCategoryFilter();
    initSidebarToggle();
    initCounterAnimation();
    highlightActiveNavLink();
});

/* ============================================================
   SUBJECT SEARCH (index page)
   ============================================================ */
function initSubjectSearch() {
    const input = document.getElementById('subjectSearch');
    if (!input) return;
    input.addEventListener('input', applyFilters);
}

/* ============================================================
   CATEGORY FILTER TABS (index page)
   ============================================================ */
function initCategoryFilter() {
    const tabs = document.querySelectorAll('.filter-tab');
    if (!tabs.length) return;

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
            applyFilters();
        });
    });
}

function applyFilters() {
    const searchVal = (document.getElementById('subjectSearch')?.value || '').toLowerCase().trim();
    const activeTab   = document.querySelector('.filter-tab.active');
    const activeCategory = activeTab ? activeTab.dataset.category : 'all';

    const cards = document.querySelectorAll('.subject-card');
    let visible = 0;

    cards.forEach(function (card) {
        const name     = (card.dataset.name     || '').toLowerCase();
        const desc     = (card.dataset.desc     || '').toLowerCase();
        const category = (card.dataset.category || '');

        const matchSearch   = !searchVal || name.includes(searchVal) || desc.includes(searchVal);
        const matchCategory = activeCategory === 'all' || category === activeCategory;

        if (matchSearch && matchCategory) {
            card.classList.remove('hidden');
            visible++;
        } else {
            card.classList.add('hidden');
        }
    });

    const noResults = document.getElementById('noResults');
    if (noResults) noResults.classList.toggle('show', visible === 0);
}

/* ============================================================
   SIDEBAR TOGGLE (dashboard)
   ============================================================ */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        if (overlay) overlay.classList.toggle('show');
    });

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // Close sidebar on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
        }
    });
}

/* ============================================================
   ANIMATED COUNTERS (dashboard stats)
   ============================================================ */
function initCounterAnimation() {
    const counters = document.querySelectorAll('[data-count]');
    if (!counters.length) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.6 });

    counters.forEach(function (el) { observer.observe(el); });
}

function animateCounter(el) {
    const target   = parseInt(el.dataset.count, 10);
    const duration = 1400;
    const startTime = performance.now();

    function update(currentTime) {
        const elapsed  = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // ease-out cubic
        const eased = 1 - Math.pow(1 - progress, 3);
        const value = Math.round(eased * target);
        el.textContent = value.toLocaleString('vi-VN');
        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}

/* ============================================================
   ACTIVE NAV LINK (dashboard sidebar)
   ============================================================ */
function highlightActiveNavLink() {
    const current = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.sidebar-nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && href === current) link.classList.add('active');
    });
}

/* ============================================================
   SUBJECT "START" BUTTON CLICK (index page)
   Prevent card link propagation when clicking the button directly
   ============================================================ */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.card-start-btn[data-href]');
    if (btn) {
        e.preventDefault();
        window.location.href = btn.dataset.href;
    }
});

/* ============================================================
   SEARCH CLEAR on Escape (index page)
   ============================================================ */
document.addEventListener('keydown', function (e) {
    const input = document.getElementById('subjectSearch');
    if (input && e.key === 'Escape') {
        input.value = '';
        applyFilters();
        input.blur();
    }
});
