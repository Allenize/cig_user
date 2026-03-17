/**
 * no_back.js
 * Prevents browser back/forward buttons from bypassing login/logout.
 * Include on EVERY protected page AND the login/logout pages.
 *
 * Strategy:
 *  - Protected pages: push a dummy state so back = "nothing to go back to"
 *    and intercept popstate to re-push (trapping the user inside).
 *    On actual session loss, server redirects handle it.
 *  - Login/logout pages: replace history so forward can't re-enter the app,
 *    and if a session exists, redirect to dashboard immediately.
 */
(function () {
    var PAGE_TYPE = window.__PAGE_TYPE || 'protected'; // 'login' | 'logout' | 'protected'
    var DASH_URL  = window.__DASH_URL  || './org-dashboard/php/dashboard.php';
    var LOGIN_URL = window.__LOGIN_URL || 'index.php';

    /* ── Login page ────────────────────────────────────────────────────── */
    if (PAGE_TYPE === 'login') {
        // Replace current history entry so you can't "forward" back in
        history.replaceState({ page: 'login' }, '', window.location.href);

        // If session cookie still alive (logged in), push back to dashboard
        // We check via a lightweight fetch to avoid PHP echo on the login page
        fetch('./org-dashboard/php/ping_session.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.ok) {
                // Still logged in — redirect back to dashboard
                window.location.replace(DASH_URL);
            }
        })
        .catch(function () {});

        // Block forward navigation from here
        window.addEventListener('popstate', function () {
            history.pushState({ page: 'login' }, '', window.location.href);
        });
        return;
    }

    /* ── Logout page ───────────────────────────────────────────────────── */
    if (PAGE_TYPE === 'logout') {
        // Kill history so back button can't re-enter the app
        history.replaceState({ page: 'logout' }, '', window.location.href);

        window.addEventListener('popstate', function () {
            // Any navigation attempt → stay on login
            history.replaceState({ page: 'logout' }, '', LOGIN_URL);
            window.location.replace(LOGIN_URL);
        });
        return;
    }

    /* ── Protected pages ───────────────────────────────────────────────── */
    // Push a sentinel state on top of history
    history.pushState({ page: 'app', ts: Date.now() }, '', window.location.href);

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.page === 'app') {
            // Still in app — re-push to prevent actually going back
            history.pushState({ page: 'app', ts: Date.now() }, '', window.location.href);
            return;
        }

        // Went past our sentinel — validate session server-side
        fetch('./ping_session.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (!d.ok) {
                // Session gone — go to login
                window.location.replace(LOGIN_URL);
            } else {
                // Session alive — re-push to trap back button
                history.pushState({ page: 'app', ts: Date.now() }, '', window.location.href);
            }
        })
        .catch(function () {
            // Network error — re-push to be safe
            history.pushState({ page: 'app', ts: Date.now() }, '', window.location.href);
        });
    });

    // Also block forward navigation away from the app
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            // Page was served from bfcache (back-forward cache)
            // Force a server-side session check
            fetch('./ping_session.php', { method: 'POST', credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.ok) window.location.replace(LOGIN_URL);
            })
            .catch(function () { window.location.replace(LOGIN_URL); });
        }
    });
})();