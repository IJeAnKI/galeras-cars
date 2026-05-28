<?php
include('config/database.php');

// Verificar conexiones
if (!$local_conn) {
    echo "Error: No hay conexión a la base de datos local";
    exit();
}

// get data con validación
$f_name = isset($_POST['fname']) ? trim($_POST['fname']) : '';
$l_name = isset($_POST['lname']) ? trim($_POST['lname']) : '';
$e_mail = isset($_POST['email']) ? trim($_POST['email']) : '';
$p_sword = isset($_POST['pasword']) ? $_POST['pasword'] : '';
$m_phone = isset($_POST['mphone']) ? trim($_POST['mphone']) : '';

// Validar que los campos requeridos no estén vacíos
if (empty($f_name) || empty($l_name) || empty($e_mail) || empty($p_sword)) {
    echo "<script>alert('Error: Todos los campos son obligatorios'); window.location='register.html';</script>";
    exit();
}

$enc_pass = password_hash($p_sword, PASSWORD_BCRYPT);

// ========== PROCESAR FOTO DE PERFIL ==========
$profile_photo_path = null;

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_photo']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    $filesize = $_FILES['profile_photo']['size'];
    
    // Verificar extensión
    if (!in_array(strtolower($filetype), $allowed)) {
        echo "<script>alert('Error: Solo se permiten archivos JPG, JPEG, PNG y GIF'); window.location='register.html';</script>";
        exit();
    }
    
    // Verificar tamaño (máximo 2MB)
    if ($filesize > 2097152) {
        echo "<script>alert('Error: La imagen no debe superar los 2MB'); window.location='register.html';</script>";
        exit();
    }
    
    // Crear nombre único para la imagen
    $new_filename = uniqid() . '_' . time() . '.' . $filetype;
    $upload_path = 'uploads/profiles/' . $new_filename;
    
    // Crear carpeta si no existe
    if (!file_exists('uploads/profiles/')) {
        mkdir('uploads/profiles/', 0777, true);
    }
    
    // Mover archivo a la carpeta
    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
        $profile_photo_path = $upload_path;
    } else {
        echo "<script>alert('Error al subir la imagen'); window.location='register.html';</script>";
        exit();
    }
}

// ========== GUARDAR EN LOCAL ==========
// Verificar qué tabla existe en local (users o users_model)
$check_table_local = "SELECT to_regclass('public.users')";
$table_exists_local = pg_query($local_conn, $check_table_local);
$exists_row_local = pg_fetch_array($table_exists_local);

if (!$exists_row_local[0]) {
    // Intentar con users_model
    $check_table_local2 = "SELECT to_regclass('public.users_model')";
    $table_exists_local2 = pg_query($local_conn, $check_table_local2);
    $exists_row_local2 = pg_fetch_array($table_exists_local2);
    
    if (!$exists_row_local2[0]) {
        echo "Error: La tabla 'users' o 'users_model' no existe en la base de datos local.";
        exit();
    } else {
        $local_table = 'users_model';
        $local_password_col = 'pasword';
        $local_firstname_col = 'first_name';
        $local_lastname_col = 'last_name';
    }
} else {
    $local_table = 'users';
    // Verificar los nombres de columnas en local
    $check_cols = "SELECT column_name FROM information_schema.columns 
                WHERE table_name = 'users' AND column_name IN ('password', 'pasword', 'firstname', 'first_name', 'lastname', 'last_name')";
    $cols_res = pg_query($local_conn, $check_cols);
    
    $local_password_col = 'pasword'; // default
    $local_firstname_col = 'first_name';
    $local_lastname_col = 'last_name';
    
    if ($cols_res) {
        while($col = pg_fetch_assoc($cols_res)) {
            if ($col['column_name'] == 'password') $local_password_col = 'password';
            if ($col['column_name'] == 'firstname') $local_firstname_col = 'firstname';
            if ($col['column_name'] == 'lastname') $local_lastname_col = 'lastname';
        }
    }
}

// Verificar email en local
$check_email = "SELECT email FROM $local_table WHERE email = '$e_mail'";
$res_email = pg_query($local_conn, $check_email);

if ($res_email && pg_num_rows($res_email) > 0) {
    echo "<script>alert('Error: El correo electrónico \"$e_mail\" ya está registrado.'); window.location='register.html';</script>";
    exit();
}

// Verificar teléfono en local (solo si no está vacío)
if (!empty($m_phone)) {
    $check_phone = "SELECT mobile_phone FROM $local_table WHERE mobile_phone = '$m_phone'";
    $res_phone = pg_query($local_conn, $check_phone);
    
    if ($res_phone && pg_num_rows($res_phone) > 0) {
        echo "<script>alert('Error: El número de celular \"$m_phone\" ya está registrado.'); window.location='register.html';</script>";
        exit();
    }
}

// Preparar valor para teléfono (NULL si está vacío)
$phone_value = !empty($m_phone) ? "'$m_phone'" : "NULL";

// Preparar valor para foto (NULL si no hay)
$photo_value = !empty($profile_photo_path) ? "'$profile_photo_path'" : "NULL";

// Query para insertar en local
$sql_local = "INSERT INTO $local_table ($local_firstname_col, $local_lastname_col, email, mobile_phone, $local_password_col, profile_photo)
            VALUES('$f_name','$l_name','$e_mail', $phone_value, '$enc_pass', $photo_value)";

$res_local = pg_query($local_conn, $sql_local);

if (!$res_local) {
    echo "<script>alert('Error local: " . addslashes(pg_last_error($local_conn)) . "'); window.location='register.html';</script>";
    exit();
}

// ========== GUARDAR EN SUPABASE ==========
if ($supa_conn) {
    $sql_supa = "INSERT INTO users (firstname, lastname, email, mobile_phone, password, profile_photo)
                VALUES('$f_name','$l_name','$e_mail', $phone_value, '$enc_pass', $photo_value)";
    
    $res_supa = pg_query($supa_conn, $sql_supa);
    
    if ($res_supa) {
        echo "<script>alert('Registro exitoso en ambos sistemas.'); window.location='login.php';</script>";
        header('Location: login.php');
        exit();
    } else {
        $supa_error = pg_last_error($supa_conn);
        echo "<script>alert('Registro guardado solo localmente. Error en Supabase: " . addslashes($supa_error) . "'); window.location='login.php';</script>";
    }
} else {
    echo "<script>alert('Registro exitoso (solo local).'); window.location='login.php';</script>";
}
?>