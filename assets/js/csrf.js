/**
 * CSRF Token Interceptor
 * -----------------------
 * <meta name="csrf-token"> etiketindeki tokeni okur ve aynı kökenli (same-origin)
 * tüm değiştirici (POST/PUT/PATCH/DELETE) isteklere otomatik olarak
 * "X-CSRF-Token" başlığını ekler.
 *
 * Hem native fetch() hem de jQuery AJAX (DataTables server-side dahil) kapsanır.
 * Bu sayede tek tek fetch çağrılarını değiştirmeye gerek kalmaz.
 */
(function () {
    function getToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function isSafeMethod(method) {
        method = (method || 'GET').toUpperCase();
        return method === 'GET' || method === 'HEAD' || method === 'OPTIONS' || method === 'TRACE';
    }

    function isSameOrigin(url) {
        if (!url) return true; // göreli (relative) URL => same-origin
        if (url.indexOf('http://') !== 0 && url.indexOf('https://') !== 0 && url.indexOf('//') !== 0) {
            return true;
        }
        return url.indexOf(window.location.origin) === 0;
    }

    var redirecting = false;

    function getLoginUrl() {
        var meta = document.querySelector('meta[name="login-url"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function goLogin() {
        var loginUrl = getLoginUrl();
        if (redirecting || !loginUrl) return;
        redirecting = true;
        window.location.href = loginUrl;
    }

    function isLoginPath(url) {
        try {
            var loginPath = new URL(getLoginUrl(), window.location.origin).pathname;
            return new URL(url, window.location.origin).pathname === loginPath;
        } catch (e) {
            return false;
        }
    }

    // --- 1) Native fetch() sarmalama ---
    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var url = (typeof input === 'string') ? input : (input && input.url) || '';
            var method = init.method || (typeof input === 'object' && input ? input.method : '') || 'GET';

            if (!isSafeMethod(method) && isSameOrigin(url)) {
                var headers = new Headers(
                    init.headers || (typeof input === 'object' && input ? input.headers : undefined) || {}
                );
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', getToken());
                }
                init.headers = headers;
            }
            var sameOrigin = isSameOrigin(url);
            return originalFetch.call(this, input, init).then(function (response) {
                if (sameOrigin && (response.status === 401 || (response.redirected && isLoginPath(response.url)))) {
                    goLogin();
                }
                return response;
            });
        };
    }

    // --- 2) jQuery AJAX (DataTables server-side POST dahil) ---
    if (window.jQuery) {
        window.jQuery.ajaxSetup({
            beforeSend: function (xhr, settings) {
                if (!isSafeMethod(settings.type) && !settings.crossDomain) {
                    xhr.setRequestHeader('X-CSRF-Token', getToken());
                }
            }
        });
        window.jQuery(document).ajaxError(function (event, xhr) {
            if (xhr && xhr.status === 401) goLogin();
        });
    }

    var checkUrl = (document.querySelector('meta[name="session-check-url"]') || {}).content;
    var lastCheck = 0;
    function checkSession() {
        var now = Date.now();
        if (!checkUrl || redirecting || now - lastCheck < 15000) return;
        lastCheck = now;
        window.fetch(checkUrl, { credentials: 'same-origin', cache: 'no-store' }).catch(function () {});
    }
    setInterval(checkSession, 60000);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) checkSession();
    });
    window.addEventListener('focus', checkSession);
})();
