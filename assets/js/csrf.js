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
            return originalFetch.call(this, input, init);
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
    }
})();
