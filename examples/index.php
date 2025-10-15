<?php
require_once __DIR__ . '/../src/PrettyErrorHandler.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Str.php';
require_once __DIR__ . '/../src/AuthManager.php';
require_once __DIR__ . '/../src/TwigHelper.php';
require_once __DIR__ . '/../src/Logs.php';
require_once __DIR__ . '/../vendor/autoload.php';
PrettyErrorHandler::enable();

DB::mysql('phphelper', 'root', '1');  

// DB::getRow('SELECT * FROM users');

AuthManager::init(DB::pdo(), [
    'table'           => 'users',
    'email_column'    => 'email',
    'password_column' => 'password',
    
]);

// AuthManager::createUsersTable();
// AuthManager::register('new@example.com', '1');
// $login = AuthManager::login('new@example.com', '1');

Logs::createLogsTable(); // creates a default table for the current driver

$logEntryId = null;
$logError = null;
$recentLogs = [];
$prettyJson = static function (?string $value): string {
    if ($value === null || $value === '') {
        return 'null';
    }

    $decoded = json_decode($value, true);
    if (!is_array($decoded)) {
        return (string) $value;
    }

    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
};

try {
    $logEntryId = Logs::info(
        'examples.page_rendered',
        [
            'route' => 'examples/index.php',
            'rendered_at' => date('c'),
        ],
        [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]
    );

    $recentLogs = DB::getRows('SELECT id, level, message, context, meta, created_at FROM logs ORDER BY id DESC LIMIT 5');
} catch (Throwable $e) {
    $logError = $e;
}

//  Str::prettyLog( AuthManager::user()['email'] );

$twigCard = null;
try {
    $templatesDir = __DIR__ . '/templates';
    TwigHelper::init($templatesDir);
    TwigHelper::addGlobal('app_name', 'PHP Helper');
    $twigCard = TwigHelper::render('welcome.twig', [
        'title' => 'TwigHelper in Action',
        'message' => 'This block is rendered from a Twig template stored in examples/templates.',
        'daysago' => strtotime('-2 days'),
    ]);
} catch (Throwable $twigError) {
    $twigCard = '<div class="alert alert-warning mb-0">Twig example unavailable: '
        . htmlspecialchars($twigError->getMessage(), ENT_QUOTES) . '</div>';
}

?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Helper — Examples</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 3.5rem; }
    </style>
    <!-- Optional: Add a favicon or custom styles here -->
    <!-- This page intentionally keeps PHP minimal; see example.php for CLI/HTML helper demos. -->
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="#">PHP Helper</a>
    </div>
</nav>

<main class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h3 mb-3">Examples</h1>
                    <p class="text-muted">Simple index page styled with Bootstrap.</p>
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Run helper demos (DB, Str, Format, Http)
                            <a class="btn btn-sm btn-primary" href="example.php">Open example.php</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <?php echo $twigCard; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h4 mb-3">Logs::log example</h2>
                    <p class="text-muted">
                        Every visit writes a structured log entry. <code>context</code> stores searchable fields and
                        <code>meta</code> keeps auxiliary request data.
                    </p>

                    <?php if ($logError !== null): ?>
                        <div class="alert alert-danger" role="alert">
                            Could not write log entry:
                            <?php echo htmlspecialchars($logError->getMessage(), ENT_QUOTES); ?>
                        </div>
                    <?php elseif ($logEntryId !== null): ?>
                        <div class="alert alert-success" role="alert">
                            Logged this page view as entry #<?php echo htmlspecialchars($logEntryId, ENT_QUOTES); ?>.
                        </div>
                    <?php endif; ?>

                    <?php if ($recentLogs === []): ?>
                        <p class="mb-0">No logs captured yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Level</th>
                                        <th>Message</th>
                                        <th>Context</th>
                                        <th>Meta</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string) $log['id'], ENT_QUOTES); ?></td>
                                        <td><?php echo htmlspecialchars((string) $log['level'], ENT_QUOTES); ?></td>
                                        <td><?php echo htmlspecialchars((string) $log['message'], ENT_QUOTES); ?></td>
                                        <td><pre class="mb-0 small"><?php echo htmlspecialchars($prettyJson($log['context'] ?? null), ENT_QUOTES); ?></pre></td>
                                        <td><pre class="mb-0 small"><?php echo htmlspecialchars($prettyJson($log['meta'] ?? null), ENT_QUOTES); ?></pre></td>
                                        <td><?php echo htmlspecialchars((string) $log['created_at'], ENT_QUOTES); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="alert alert-info" role="alert">
                <strong>Tip:</strong>   The custom error handler is enabled; errors render with details in development.
            </div>
        </div>
    </div>
</main>

<footer class="container py-4">
    <div class="text-center text-muted">&copy; <?php echo date('Y'); ?> PHP Helper</div>
    <div class="text-center small text-muted">examples/index.php</div>
    
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
