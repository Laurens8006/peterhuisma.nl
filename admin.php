<?php
session_start();

$adminPassword = 'peter2026';
$stateFile = __DIR__ . '/data/availability.json';
$defaultState = [
    'available' => true,
    'heading' => 'Beschikbaar voor nieuwe projecten',
    'description' => 'Open voor freelance-opdrachten, portfolio\'s en moderne zakelijke websites.',
    'label' => 'Nu beschikbaar'
];

if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

function readState($stateFile, $defaultState)
{
    if (!file_exists($stateFile)) {
        return $defaultState;
    }

    $json = file_get_contents($stateFile);
    if ($json === false) {
        return $defaultState;
    }

    $decoded = json_decode($json, true);
    return is_array($decoded) ? array_merge($defaultState, $decoded) : $defaultState;
}

function writeState($stateFile, $state)
{
    file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$state = readState($stateFile, $defaultState);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['password']) || $_POST['password'] !== $adminPassword) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'Onjuiste toegangscode.']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'save') {
        if (empty($_SESSION['admin_logged_in'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Niet ingelogd.']);
            exit;
        }

        $nextState = [
            'available' => !empty($_POST['available']),
            'heading' => trim((string)($_POST['heading'] ?? '')) ?: $defaultState['heading'],
            'description' => trim((string)($_POST['description'] ?? '')) ?: $defaultState['description'],
            'label' => trim((string)($_POST['label'] ?? '')) ?: ($nextState['available'] ?? true ? 'Nu beschikbaar' : 'Momenteel niet beschikbaar')
        ];

        $nextState['label'] = trim((string)($nextState['label'] ?? '')) ?: ($nextState['available'] ? 'Nu beschikbaar' : 'Momenteel niet beschikbaar');
        writeState($stateFile, $nextState);
        echo json_encode(['ok' => true, 'state' => $nextState]);
        exit;
    }

    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['ok' => true]);
        exit;
    }
}

if (!empty($_SESSION['admin_logged_in'])) {
    $state = readState($stateFile, $defaultState);
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Admin | Beschikbaarheid</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --bg: #080808;
            --card: #111111;
            --border: rgba(255, 255, 255, 0.08);
            --pink: #8b5cf6;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: #f5f5f5;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 9999px;
            background: var(--pink);
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn.secondary {
            background: transparent;
            border: 1px solid var(--border);
            color: #ccc;
        }
    </style>
</head>

<body>
    <main class="min-h-screen flex items-center justify-center px-6 py-16">
        <?php if (empty($_SESSION['admin_logged_in'])): ?>
            <div id="login-panel" class="card max-w-md w-full p-8 md:p-10">
                <p class="text-[11px] uppercase tracking-[0.24em] mb-4" style="color:var(--pink)">Admin toegang</p>
                <h1 class="text-3xl md:text-4xl font-semibold mb-4">Log in</h1>
                <p class="text-sm text-gray-400 mb-6">Voer de toegangscode in om de beschikbaarheidsinstellingen te bewerken.</p>
                <label class="block">
                    <span class="text-sm text-gray-400">Toegangscode</span>
                    <input id="password-input" type="password" class="mt-2 w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-white" placeholder="Typ de code" />
                </label>
                <div class="mt-6 flex flex-wrap gap-3">
                    <button id="login-btn" class="btn">Inloggen</button>
                    <a href="../index.html" class="btn secondary">Terug naar site</a>
                </div>
                <p id="login-error" class="mt-4 text-sm text-red-400 hidden">Onjuiste toegangscode.</p>
            </div>
        <?php else: ?>
            <div id="admin-panel" class="card max-w-2xl w-full p-8 md:p-10">
                <p class="text-[11px] uppercase tracking-[0.24em] mb-4" style="color:var(--pink)">Admin</p>
                <h1 class="text-3xl md:text-4xl font-semibold mb-4">Beschikbaarheid beheren</h1>
                <p class="text-sm text-gray-400 mb-8">Hier kun je de status van je website aanpassen. De homepage reageert direct op veranderingen.</p>
                <div class="grid gap-4">
                    <label class="flex items-center justify-between rounded-2xl border border-white/10 p-4">
                        <span>Beschikbaar voor nieuwe projecten</span>
                        <input id="available-toggle" type="checkbox" class="h-5 w-5 accent-violet-500" <?php echo !empty($state['available']) ? 'checked' : ''; ?> />
                    </label>
                    <label class="block">
                        <span class="text-sm text-gray-400">Titel</span>
                        <input id="heading-input" class="mt-2 w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-white" value="<?php echo htmlspecialchars($state['heading'] ?? 'Beschikbaar voor nieuwe projecten'); ?>" />
                    </label>
                    <label class="block">
                        <span class="text-sm text-gray-400">Beschrijving</span>
                        <textarea id="description-input" class="mt-2 min-h-[100px] w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-white"><?php echo htmlspecialchars($state['description'] ?? 'Open voor freelance-opdrachten, portfolio\'s en moderne zakelijke websites.'); ?></textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm text-gray-400">Label</span>
                        <input id="label-input" class="mt-2 w-full rounded-xl border border-white/10 bg-black/20 px-4 py-3 text-white" value="<?php echo htmlspecialchars($state['label'] ?? 'Nu beschikbaar'); ?>" />
                    </label>
                </div>
                <div class="mt-8 flex flex-wrap gap-3">
                    <button id="save-btn" class="btn">Opslaan</button>
                    <button id="logout-btn" class="btn secondary">Uitloggen</button>
                    <a href="../index.html" class="btn secondary">Terug naar site</a>
                </div>
                <p id="status-message" class="mt-5 text-sm text-emerald-400 hidden">Wijzigingen opgeslagen.</p>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const adminPassword = 'peter2026';

        document.getElementById('login-btn')?.addEventListener('click', async () => {
            const password = document.getElementById('password-input').value;
            const error = document.getElementById('login-error');
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    password,
                    action: 'login'
                })
            });
            const data = await res.json();
            if (data.ok) {
                window.location.reload();
            } else {
                error.classList.remove('hidden');
            }
        });

        document.getElementById('save-btn')?.addEventListener('click', async () => {
            const payload = new URLSearchParams({
                password: adminPassword,
                action: 'save',
                available: document.getElementById('available-toggle').checked ? '1' : '0',
                heading: document.getElementById('heading-input').value,
                description: document.getElementById('description-input').value,
                label: document.getElementById('label-input').value
            });
            const res = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: payload
            });
            const data = await res.json();
            const message = document.getElementById('status-message');
            if (data.ok) {
                message.classList.remove('hidden');
                message.textContent = 'Wijzigingen opgeslagen. De homepage wordt bijgewerkt.';
            } else {
                message.classList.remove('hidden');
                message.textContent = data.message || 'Opslaan mislukt.';
                message.classList.add('text-red-400');
            }
        });

        document.getElementById('logout-btn')?.addEventListener('click', async () => {
            await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=logout'
            });
            window.location.reload();
        });
    </script>
</body>

</html>