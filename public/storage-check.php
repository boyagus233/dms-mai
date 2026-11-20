<?php

/**
 * Storage Symlink Checker & Repair Tool
 * Access: /storage-check.php
 */

define('LARAVEL_START', microtime(true));

// 1. Load Laravel Bootstrap
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

if (ob_get_length()) ob_end_clean();

$publicStoragePath = public_path('storage');
$realStoragePath = storage_path('app/public');

$action = $_POST['action'] ?? '';
$message = '';

if ($action === 'repair') {
    try {
        // 1. Remove existing if it's a folder or broken link
        if (file_exists($publicStoragePath) || is_link($publicStoragePath)) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows handling
                if (is_dir($publicStoragePath) && !is_link($publicStoragePath)) {
                    exec("rd /s /q " . escapeshellarg($publicStoragePath));
                } else {
                    unlink($publicStoragePath);
                }
            } else {
                // Linux/Unix handling
                exec("rm -rf " . escapeshellarg($publicStoragePath));
            }
        }

        // 2. Create the symlink using Artisan
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        $output = \Illuminate\Support\Facades\Artisan::output();

        $message = "<div class='alert alert-success'>Repair attempted. Result: <pre>$output</pre></div>";
    } catch (\Exception $e) {
        $message = "<div class='alert alert-danger'>Repair failed: " . $e->getMessage() . "</div>";
    }
}

// Re-check status
$exists = file_exists($publicStoragePath);
$isLink = is_link($publicStoragePath);
$working = false;

// Windows detection trick: realpath of a link returns the target,
// realpath of a normal folder returns its own path.
$resolvedPublicPath = realpath($publicStoragePath);
$resolvedRealTarget = realpath($realStoragePath);

if ($exists) {
    if ($resolvedPublicPath === $resolvedRealTarget && !empty($resolvedRealTarget)) {
        // If they resolve to the same location, it's a working link/junction!
        $working = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Laravel Storage Link Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; padding: 40px; font-family: sans-serif; }
        .card { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: none; }
        .status-box { padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .status-ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-fail { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        code { background: #eee; padding: 2px 5px; border-radius: 4px; color: #d63384; }
    </style>
</head>
<body>
<div class="container" style="max-width: 800px;">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title mb-4 text-center">Storage Symlink Status</h3>

            <?= $message ?>

            <div class="status-box <?= $working ? 'status-ok' : 'status-fail' ?>">
                <h1 class="mb-0">
                    <?= $working ? '✅ CONNECTED' : '❌ DISCONNECTED' ?>
                </h1>
                <p class="mt-2">
                    <?= $working ? 'Your storage symlink is working correctly.' : 'There is an issue with your storage link.' ?>
                </p>
            </div>

            <table class="table table-bordered">
                <tr>
                    <th width="30%">Public Path</th>
                    <td><code><?= $publicStoragePath ?></code></td>
                </tr>
                <tr>
                    <th>Real Target Path</th>
                    <td><code><?= $realStoragePath ?></code></td>
                </tr>
                <tr>
                    <th>Exists?</th>
                    <td><?= $exists ? 'Yes' : 'No' ?></td>
                </tr>
                <tr>
                    <th>Is Symlink?</th>
                    <td><?= $isLink ? 'Yes' : 'No' ?></td>
                </tr>
            </table>

            <?php if (!$working): ?>
            <div class="alert alert-warning">
                <h5>Possible Causes:</h5>
                <ul>
                    <li>The <code>public/storage</code> folder was created as a normal directory instead of a shortcut.</li>
                    <li>The symlink was created on a different OS/Environment.</li>
                    <li>Permission issues.</li>
                </ul>
                <form method="POST">
                    <input type="hidden" name="action" value="repair">
                    <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                        AUTO-REPAIR SYMLINK
                    </button>
                </form>
            </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">Document files should be accessible at: <br>
                    <code><?= url('storage/documents/filename.pdf') ?></code></p>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="/" class="btn btn-link">Back to Application</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
