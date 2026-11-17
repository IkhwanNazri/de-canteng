<!DOCTYPE html>
<html lang="ms">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#063B2D">
        <title inertia>{{ config('app.name', 'DeBatik') }}</title>
        @if (config('app.debug'))
            {{-- Papar ralat JavaScript di skrin (berguna untuk iPhone tanpa Web Inspector) --}}
            <script>
                (function () {
                    function show(message) {
                        var box = document.getElementById('js-error-box');
                        if (!box) {
                            box = document.createElement('pre');
                            box.id = 'js-error-box';
                            box.style.cssText = 'position:fixed;left:8px;right:8px;bottom:8px;z-index:99999;max-height:45vh;overflow:auto;white-space:pre-wrap;background:#7f1d1d;color:#fff;font:12px/1.4 monospace;padding:10px;border-radius:10px';
                            (document.body || document.documentElement).appendChild(box);
                        }
                        box.textContent += message + '\n\n';
                    }
                    window.addEventListener('error', function (e) { show('Ralat JS: ' + e.message + (e.filename ? '\n' + e.filename + ':' + e.lineno : '')); });
                    window.addEventListener('unhandledrejection', function (e) { show('Ralat JS (promise): ' + (e.reason && (e.reason.stack || e.reason.message) || e.reason)); });
                    window.__showJsError = show;
                })();
            </script>
        @endif
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
