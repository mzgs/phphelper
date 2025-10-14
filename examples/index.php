<?php
require_once __DIR__ . '/../src/PrettyErrorHandler.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/AuthManager.php';
PrettyErrorHandler::enable();

DB::mysql('phphelper', 'root', '1');  

// DB::getRow('SELECT * FROM users');

AuthManager::configure([
    "mysql" => [
        'pdo' => DB::pdo()
    ],
]);
AuthManager::createUsersTable()
 

?>
<!doctype html>
<html lang="en">
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

            <div class="alert alert-info" role="alert">
                <strong>Tip:</strong> The custom error handler is enabled; errors render with details in development.
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
