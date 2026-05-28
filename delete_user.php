<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('config/database.php');

$user_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Obtener la foto antes de eliminar
$sql = "SELECT profile_photo FROM users WHERE id = $user_id";
$result = pg_query($local_conn, $sql);
$user = pg_fetch_assoc($result);

// Eliminar la foto del servidor
if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
    unlink($user['profile_photo']);
}

// Eliminar usuario
$sql_delete = "DELETE FROM users WHERE id = $user_id";
$result_delete = pg_query($local_conn, $sql_delete);

if ($result_delete) {
    echo "<script>alert('Usuario eliminado correctamente'); window.location='users_list.php';</script>";
} else {
    echo "<script>alert('Error al eliminar: " . pg_last_error($local_conn) . "'); window.location='users_list.php';</script>";
}
?>