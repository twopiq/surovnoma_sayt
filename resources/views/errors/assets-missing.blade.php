@php
    $manifestPath = $manifestPath ?? public_path('build/manifest.json');
@endphp

<!DOCTYPE html>
<html lang="uz">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>500 - Assetlar topilmadi</title>
        <style>
            :root {
                --shell: #f4fbf7;
                --surface: #ffffff;
                --surface-soft: #eef8f2;
                --text: #10201c;
                --muted: #5d706a;
                --line: #c9ddd4;
                --accent: #0f8f6b;
                --accent-strong: #0a6f55;
                --danger: #9f1239;
                --danger-soft: #fff0f1;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--shell);
                color: var(--text);
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            }

            main {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 32px 18px;
            }

            .wrap {
                width: min(960px, 100%);
                display: grid;
                gap: 24px;
            }

            .brand {
                display: flex;
                align-items: center;
                gap: 12px;
                color: var(--accent-strong);
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .logo {
                display: grid;
                width: 44px;
                height: 44px;
                place-items: center;
                border-radius: 12px;
                background: var(--accent);
                color: #ffffff;
                font-weight: 900;
            }

            .panel {
                border: 1px solid var(--line);
                border-radius: 18px;
                background: var(--surface);
                box-shadow: 0 20px 70px rgba(15, 61, 53, 0.12);
                overflow: hidden;
            }

            .hero {
                display: grid;
                gap: 18px;
                padding: clamp(28px, 6vw, 56px);
                background: linear-gradient(135deg, var(--surface) 0%, var(--surface-soft) 100%);
                border-bottom: 1px solid var(--line);
            }

            .code {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                gap: 8px;
                border-radius: 999px;
                background: var(--danger-soft);
                color: var(--danger);
                padding: 8px 12px;
                font-size: 13px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            h1 {
                margin: 0;
                max-width: 760px;
                font-size: clamp(34px, 7vw, 72px);
                line-height: 0.92;
                letter-spacing: -0.04em;
            }

            .lead {
                margin: 0;
                max-width: 700px;
                color: var(--muted);
                font-size: clamp(16px, 2vw, 20px);
                line-height: 1.55;
            }

            .content {
                display: grid;
                gap: 18px;
                padding: clamp(22px, 4vw, 36px);
            }

            .notice {
                display: grid;
                gap: 8px;
                border: 1px solid var(--line);
                border-radius: 14px;
                background: #fbfffd;
                padding: 18px;
            }

            .label {
                color: var(--muted);
                font-size: 13px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            code {
                display: block;
                overflow-x: auto;
                border-radius: 10px;
                background: #10201c;
                color: #d9f6ea;
                padding: 12px;
                font-size: 13px;
                line-height: 1.5;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
            }

            a,
            button {
                border: 0;
                border-radius: 999px;
                cursor: pointer;
                font: inherit;
                font-weight: 800;
                text-decoration: none;
                padding: 12px 16px;
            }

            a {
                background: var(--accent);
                color: #ffffff;
            }

            button {
                background: var(--surface-soft);
                color: var(--text);
            }

            a:hover {
                background: var(--accent-strong);
            }

            button:hover {
                outline: 1px solid var(--line);
            }
        </style>
    </head>
    <body>
        <main>
            <div class="wrap">
                <div class="brand">
                    <span class="logo">RTT</span>
                    <span>Elektron murojaatlar tizimi</span>
                </div>

                <section class="panel">
                    <div class="hero">
                        <div class="code">500 / Build assetlar topilmadi</div>
                        <h1>Sayt fayllari to'liq yuklanmagan</h1>
                        <p class="lead">
                            Tizim ishlashi uchun frontend build fayllari kerak. Serverda
                            <strong>public/build/manifest.json</strong> topilmadi yoki noto'g'ri joyga yuklangan.
                        </p>
                    </div>

                    <div class="content">
                        <div class="notice">
                            <div class="label">Tekshirilgan fayl</div>
                            <code>{{ $manifestPath }}</code>
                        </div>

                        <div class="notice">
                            <div class="label">Administrator uchun yechim</div>
                            <code>npm install
npm run build
php artisan optimize:clear
php artisan config:cache</code>
                        </div>

                        <div class="actions">
                            <button type="button" onclick="window.location.reload()">Qayta yuklash</button>
                            <a href="{{ url('/') }}">Asosiy sahifa</a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
