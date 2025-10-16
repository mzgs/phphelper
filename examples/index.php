<?php
require_once __DIR__ . '/../src/PrettyErrorHandler.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/Str.php';
require_once __DIR__ . '/../src/AuthManager.php';
require_once __DIR__ . '/../src/TwigHelper.php';
require_once __DIR__ . '/../src/Logs.php';
require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Format.php';
require_once __DIR__ . '/../src/AIChat.php';
require_once __DIR__ . '/../src/App.php';
require_once __DIR__ . '/../vendor/autoload.php';
PrettyErrorHandler::enable();


if (App::isProduction()) {
    DB::mysql('phphelper', 'root', '1');
} else {
    DB::mysql('phphelper', 'root', '1');
}   


// DB::getRow('SELECT * FROM users');

AuthManager::init([
    'table'           => 'users',
    'email_column'    => 'email',
    'password_column' => 'password',
    
]);

// AuthManager::createUsersTable();
// AuthManager::register('new@example.com', '1');
// $login = AuthManager::login('new@example.com', '1');


//  Str::prettyLog( AuthManager::user()['email'] );

// AIChat::init([
//     'api_key' => getenv('OPENAI_API_KEY'),
//     'model' => 'gpt-4o-mini',
// ]);

// $res = AIChat::reply(
//     'summary word highest mountain with this json format { "mountain": "name", "height": 111 }',
    
// );
// Str::prettyLog($res);

Config::init();
Config::createConfigTable();
Config::set('site_name', 'PHP Helper');

Str::prettyLog(App::isLocal());

$twigCard = null;
try {
    $templatesDir = __DIR__ . '/templates';
    TwigHelper::init($templatesDir);
    TwigHelper::addGlobal('app_name', 'PHP Helper');
    $twigCard = TwigHelper::render('welcome.twig', [
        'title'   => 'TwigHelper in Action',
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
