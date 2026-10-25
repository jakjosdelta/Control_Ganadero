<?php
require_once 'includes/functions.php';

$id = $_GET['id'] ?? 0;

if ($id && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        eliminarAnimal($id);
        header('Location: index.php?mensaje=eliminado');
    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
    }
} else {
    header('Location: index.php');
}
exit;
?>