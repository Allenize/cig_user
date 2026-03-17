/**
 * session_manager.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Handles:
 *  1. Session idle timeout with countdown warning modal
 *  2. "Stay Logged In" ping
 *  3. Forced logout when session expires
 *  4. Real-time credential verification polling (for unverified users)
 *  5. Live page state sync (navbar lock updates)
 * ─────────────────────────────────────────────────────────────────────────────
 * Usage: include in every protected page AFTER navbar.
 *   <script src="../js/session_manager.js"></script>
 * Pass config via data attributes on the script tag or window.__SM config:
 *   window.__SM = { timeout: 1800, warn: 120, verified: false };
 * ─────────────────────────────────────────────────────────────────────────────
 */
(function () {
    'use strict';

    /* ── Config ──────────────────────────────────────────────────────────── */
    var cfg = window.__SM || {};
    var IDLE_TIMEOUT   = (cfg.timeout  || 1800) * 1000; // default 30 min
    var WARN_BEFORE    = (cfg.warn     || 120)  * 1000; // warn 2 min before
    var PING_URL       = cfg.pingUrl   || '../php/ping_session.php';
    var LOGOUT_URL     = cfg.logoutUrl || '../php/logout.php';
    var LOGIN_URL      = cfg.loginUrl  || 'index.php';
    var CHECK_URL      = cfg.checkUrl  || '../php/credential_verify_save.php';
    var IS_VERIFIED    = cfg.verified  !== false;        // default true unless told otherwise
    var POLL_INTERVAL  = 30000; // 30s credential poll

    /* ── Inject styles ───────────────────────────────────────────────────── */
    var style = document.createElement('style');
    style.textContent = [
        '#sm-overlay{position:fixed;inset:0;z-index:99998;background:rgba(0,20,10,.55);',
        'backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;}',
        '#sm-overlay.open{display:flex;}',
        '#sm-modal{background:#fff;border-radius:22px;width:92vw;max-width:420px;',
        'overflow:hidden;box-shadow:0 28px 70px rgba(0,20,10,.3);}',
        '#sm-head{background:linear-gradient(135deg,#1a3d2b,#2d6a4f);padding:1.2rem 1.5rem;',
        'display:flex;align-items:center;gap:.8rem;}',
        '#sm-head i{font-size:1.2rem;color:#52b788;}',
        '#sm-head h3{font-size:.97rem;font-weight:700;color:#fff;margin:0;}',
        '#sm-body{padding:1.3rem 1.5rem;}',
        '#sm-body p{font-size:.88rem;color:#374151;line-height:1.6;margin:0 0 1rem;}',
        '#sm-countdown{font-size:2rem;font-weight:800;color:#1a3d2b;text-align:center;',
        'padding:.6rem;background:#f0faf5;border-radius:10px;margin-bottom:1rem;',
        'border:2px solid #c3e0cc;letter-spacing:.05em;}',
        '#sm-countdown.urgent{color:#dc2626;background:#fff5f5;border-color:#fca5a5;}',
        '#sm-foot{padding:.85rem 1.5rem;border-top:1px solid #eef2ef;',
        'display:flex;justify-content:flex-end;gap:.6rem;background:#fafcfc;}',
        '.sm-btn{padding:.55rem 1.2rem;border-radius:8px;font-size:.88rem;font-weight:700;',
        'cursor:pointer;border:none;font-family:inherit;display:inline-flex;align-items:center;gap:.4rem;}',
        '.sm-btn.logout{background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;}',
        '.sm-btn.logout:hover{background:#e2e8f0;}',
        '.sm-btn.stay{background:linear-gradient(135deg,#1a3d2b,#2d6a4f);color:#fff;',
        'box-shadow:0 3px 10px rgba(26,61,43,.25);}',
        '.sm-btn.stay:hover{filter:brightness(1.1);}',
        /* Verified toast — persistent, user must click */
        '#sm-verified-toast{position:fixed;bottom:1.5rem;right:1.5rem;z-index:99999;',
        'background:linear-gradient(135deg,#1a6335,#2d6a4f);color:#fff;',
        'border-radius:16px;padding:1rem 1.2rem;',
        'display:flex;align-items:center;gap:.75rem;',
        'box-shadow:0 6px 28px rgba(0,0,0,.25);opacity:0;transform:translateY(12px);',
        'pointer-events:none;max-width:340px;flex-direction:column;}',
        '#sm-verified-toast.show{opacity:1;transform:translateY(0);pointer-events:auto;}',
        '#sm-verified-toast .toast-top{display:flex;align-items:center;gap:.6rem;width:100%;}',
        '#sm-verified-toast .toast-msg{font-size:.88rem;font-weight:600;flex:1;line-height:1.4;}',
        '#sm-verified-toast .toast-btn{',
        'width:100%;padding:.5rem;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.35);',
        'color:#fff;border-radius:8px;font-size:.83rem;font-weight:700;cursor:pointer;',
        'font-family:inherit;display:flex;align-items:center;justify-content:center;gap:.4rem;}',
        '#sm-verified-toast .toast-btn:hover{background:rgba(255,255,255,.32);}',
        /* Revoked banner */
        '#sm-revoked-banner{position:fixed;inset:0;z-index:99999;',
        'background:rgba(0,0,0,.7);backdrop-filter:blur(6px);',
        'display:none;align-items:center;justify-content:center;}',
        '#sm-revoked-banner.open{display:flex;}',
        '#sm-revoked-card{background:#fff;border-radius:20px;width:92vw;max-width:400px;',
        'overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.35);}',
        '#sm-revoked-head{background:linear-gradient(135deg,#7f1d1d,#dc2626);',
        'padding:1.2rem 1.5rem;display:flex;align-items:center;gap:.8rem;}',
        '#sm-revoked-head i{font-size:1.2rem;color:#fca5a5;}',
        '#sm-revoked-head h3{font-size:.97rem;font-weight:700;color:#fff;margin:0;}',
        '#sm-revoked-body{padding:1.3rem 1.5rem;font-size:.88rem;color:#374151;line-height:1.6;}',
        '#sm-revoked-body p{margin:0 0 .5rem;}',
        '#sm-revoked-bar{height:4px;background:#fee2e2;margin:0 1.5rem 1.2rem;border-radius:20px;overflow:hidden;}',
        '#sm-revoked-fill{height:100%;background:#dc2626;border-radius:20px;',
        'animation:revokedFill 3s linear forwards;}',
        '@keyframes revokedFill{from{width:100%}to{width:0%}}',
    ].join('');
    document.head.appendChild(style);

    /* ── Build modal DOM ─────────────────────────────────────────────────── */
    var overlay = document.createElement('div');
    overlay.id  = 'sm-overlay';
    overlay.innerHTML =
        '<div id="sm-modal">' +
            '<div id="sm-head">' +
                '<i class="fas fa-clock"></i>' +
                '<h3>Session Expiring Soon</h3>' +
            '</div>' +
            '<div id="sm-body">' +
                '<p>You have been inactive and will be automatically logged out in:</p>' +
                '<div id="sm-countdown">02:00</div>' +
                '<p style="font-size:.8rem;color:#9ab5ac;margin:0">Click <strong>Stay Logged In</strong> to continue your session.</p>' +
            '</div>' +
            '<div id="sm-foot">' +
                '<button class="sm-btn logout" id="sm-logout-btn"><i class="fas fa-sign-out-alt"></i> Log Out</button>' +
                '<button class="sm-btn stay"   id="sm-stay-btn"><i class="fas fa-check"></i> Stay Logged In</button>' +
            '</div>' +
        '</div>';
    document.body.appendChild(overlay);

    /* Verified toast */
    var vToast = document.createElement('div');
    vToast.id  = 'sm-verified-toast';
    vToast.innerHTML =
        '<div class="toast-top">' +
            '<i class="fas fa-award" style="font-size:1.1rem;flex-shrink:0;"></i>' +
            '<span class="toast-msg">Access granted by Admin!</span>' +
        '</div>' +
        '<button class="toast-btn" onclick="window.location.href=\'dashboard.php\'">' +
            '<i class="fas fa-arrow-right"></i> Go to Dashboard' +
        '</button>';
    document.body.appendChild(vToast);

    /* Revoked banner */
    var rBanner = document.createElement('div');
    rBanner.id  = 'sm-revoked-banner';
    rBanner.innerHTML =
        '<div id="sm-revoked-card">' +
            '<div id="sm-revoked-head">' +
                '<i class="fas fa-ban"></i>' +
                '<h3>Access Revoked</h3>' +
            '</div>' +
            '<div id="sm-revoked-body">' +
                '<p>Your organization\'s accreditation access has been revoked by the CIG Admin.</p>' +
                '<p style="color:#9ab5ac;font-size:.82rem;">Redirecting to the verification page…</p>' +
            '</div>' +
            '<div id="sm-revoked-bar"><div id="sm-revoked-fill"></div></div>' +
        '</div>';
    document.body.appendChild(rBanner);

    /* ── State ───────────────────────────────────────────────────────────── */
    var warnTimer    = null;
    var countTimer   = null;
    var remaining    = WARN_BEFORE / 1000;
    var lastActivity = Date.now();
    var warningShown = false;

    /* ── Activity tracking ───────────────────────────────────────────────── */
    ['mousemove','mousedown','keydown','touchstart','scroll','click'].forEach(function(ev) {
        document.addEventListener(ev, resetActivity, { passive: true });
    });

    function resetActivity() {
        if (warningShown) return; // don't reset during warning countdown
        lastActivity = Date.now();
    }

    /* ── Main idle check loop (runs every 10 s) ──────────────────────────── */
    setInterval(function() {
        if (warningShown) return;
        var idle = Date.now() - lastActivity;
        if (idle >= IDLE_TIMEOUT - WARN_BEFORE) {
            showWarning();
        }
    }, 10000);

    /* ── Show warning modal ──────────────────────────────────────────────── */
    function showWarning() {
        warningShown = true;
        remaining    = WARN_BEFORE / 1000;
        updateCountdown();
        overlay.classList.add('open');

        countTimer = setInterval(function() {
            remaining--;
            updateCountdown();
            if (remaining <= 0) {
                clearInterval(countTimer);
                forceLogout();
            }
        }, 1000);
    }

    function updateCountdown() {
        var el  = document.getElementById('sm-countdown');
        var min = Math.floor(remaining / 60);
        var sec = remaining % 60;
        el.textContent = pad(min) + ':' + pad(sec);
        if (remaining <= 30) {
            el.classList.add('urgent');
        } else {
            el.classList.remove('urgent');
        }
    }

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    /* ── Stay logged in ──────────────────────────────────────────────────── */
    document.getElementById('sm-stay-btn').addEventListener('click', function() {
        clearInterval(countTimer);
        overlay.classList.remove('open');
        warningShown = false;
        lastActivity = Date.now();

        fetch(PING_URL, { method: 'POST', credentials: 'same-origin' })
            .catch(function() {});
    });

    /* ── Log out now ─────────────────────────────────────────────────────── */
    document.getElementById('sm-logout-btn').addEventListener('click', function() {
        clearInterval(countTimer);
        forceLogout();
    });

    function forceLogout() {
        overlay.classList.remove('open');
        window.location.href = LOGOUT_URL + '?reason=timeout';
    }

    /* ── Credential verification polling (unverified users only) ─────────── */
    if (!IS_VERIFIED) {
        var pollTimer = setInterval(function() {
            var fd = new FormData();
            fd.append('check_only', '1');
            fetch(CHECK_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.verified) {
                    clearInterval(pollTimer);
                    showVerifiedToast();
                    // No auto-redirect — user clicks the button when ready
                }
            })
            .catch(function() {});
        }, POLL_INTERVAL);
    }

    /* ── Revocation poll (verified users only) ───────────────────────────── */
    if (IS_VERIFIED) {
        var revokeTimer = setInterval(function() {
            var fd = new FormData();
            fd.append('check_only', '1');
            fetch(CHECK_URL, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.verified === false) {
                    clearInterval(revokeTimer);
                    showRevokedBanner();
                    setTimeout(function() {
                        window.location.href = 'credential_verification.php';
                    }, 3000);
                }
            })
            .catch(function() {});
        }, POLL_INTERVAL);
    }

    /* ── Verified toast ──────────────────────────────────────────────────── */
    function showVerifiedToast() {
        vToast.classList.add('show');
    }

    /* ── Revoked banner ──────────────────────────────────────────────────── */
    function showRevokedBanner() {
        document.getElementById('sm-revoked-banner').classList.add('open');
    }

    /* ── Backdrop click closes warning (resets, doesn't log out) ─────────── */
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            document.getElementById('sm-stay-btn').click();
        }
    });

})();