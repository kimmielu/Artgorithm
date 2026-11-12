<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | The Artgorithm' : 'The Artgorithm — Collaborative Code Platform'; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="assets/css/<?php echo htmlspecialchars($extraCss); ?>">
    <?php endif; ?>
</head>
<body>
<?php require __DIR__ . '/navigation.php'; ?>
<div class="toast-container" id="toastContainer">
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
        <div class="toast toast-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>
</div>
<main class="main-content">