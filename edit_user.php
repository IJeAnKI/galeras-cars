<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('config/database.php');

$user_id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id'];

// Obtener datos del usuario
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = pg_query($local_conn, $sql);
$user = pg_fetch_assoc($result);

if (!$user) {
    die("Usuario no encontrado");
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = pg_escape_string($local_conn, $_POST['firstname']);
    $lastname = pg_escape_string($local_conn, $_POST['lastname']);
    $email = pg_escape_string($local_conn, $_POST['email']);
    $mobile_phone = pg_escape_string($local_conn, $_POST['mobile_phone']);
    
    $update_fields = "firstname = '$firstname', lastname = '$lastname', email = '$email', mobile_phone = '$mobile_phone'";
    
    // Procesar nueva foto
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        $filesize = $_FILES['profile_photo']['size'];
        
        if (in_array(strtolower($filetype), $allowed) && $filesize <= 2097152) {
            // Eliminar foto anterior si existe
            if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
                unlink($user['profile_photo']);
            }
            
            // Guardar nueva foto
            $new_filename = uniqid() . '_' . time() . '.' . $filetype;
            $upload_path = 'uploads/profiles/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $update_fields .= ", profile_photo = '$upload_path'";
            }
        }
    }
    
    $sql_update = "UPDATE users SET $update_fields WHERE id = $user_id";
    $result_update = pg_query($local_conn, $sql_update);
    
    if ($result_update) {
        echo "<script>alert('Usuario actualizado correctamente'); window.location='users_list.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar: " . pg_last_error($local_conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario - Galeras Cars</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
        input, button { width: 100%; padding: 10px; margin: 10px 0; }
        .current-photo { text-align: center; margin: 20px 0; }
        .current-photo img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; }
        button { background-color: #4e73df; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #2e59d9; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Usuario</h2>
        
        <div class="current-photo">
            <h4>Foto actual:</h4>
            <?php if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])): ?>
                <img src="<?php echo $user['profile_photo']; ?>" alt="Foto de perfil">
            <?php else: ?>
                <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #4e73df; color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 40px;">
                    <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Nombre:</label>
            <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
            
            <label>Apellido:</label>
            <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            
            <label>Teléfono:</label>
            <input type="text" name="mobile_phone" value="<?php echo htmlspecialchars($user['mobile_phone']); ?>">
            
            <label>Cambiar foto de perfil:</label>
            <input type="file" name="profile_photo" accept="image/jpeg, image/png, image/jpg, image/gif">
            <small>Formatos: JPG, PNG, GIF. Máximo 2MB</small>
            
            <button type="submit">Guardar Cambios</button>
        </form>
        
        <a href="users_list.php" style="display: block; text-align: center; margin-top: 20px;">Volver a la lista</a>
    </div>
</body>
</html>