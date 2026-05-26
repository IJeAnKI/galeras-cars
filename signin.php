<?php
include('config/database.php');  // Cambiar de 'database.php' a 'config/database.php'
session_start();

if (!$local_conn) {
    echo "<script>alert('Error de conexión a la base de datos'); window.location='login.html';</script>";
    exit();
}

$e_mail = isset($_POST['email']) ? $_POST['email'] : '';
$p_asswd = isset($_POST['pswd']) ? $_POST['pswd'] : '';

if (empty($e_mail) || empty($p_asswd)) {
    echo "<script>alert('Por favor complete todos los campos'); window.location='login.html';</script>";
    exit();
}

// Determinar qué tabla y columnas usar en local
$local_table = 'users';
$password_column = 'pasword';
$firstname_col = 'first_name';
$lastname_col = 'last_name';

// Verificar si existe tabla users
$check_users = "SELECT to_regclass('public.users')";
$res_check = pg_query($local_conn, $check_users);
$row_check = pg_fetch_array($res_check);

if (!$row_check[0]) {
    $local_table = 'users_model';
    $password_column = 'pasword';
    $firstname_col = 'first_name';
    $lastname_col = 'last_name';
} else {
    // Verificar nombres de columnas en users
    $check_cols = "SELECT column_name FROM information_schema.columns 
                WHERE table_name = 'users' AND column_name IN ('password', 'pasword', 'firstname', 'first_name')";
    $cols_res = pg_query($local_conn, $check_cols);
    
    if ($cols_res) {
        while($col = pg_fetch_assoc($cols_res)) {
            if ($col['column_name'] == 'password') $password_column = 'password';
            if ($col['column_name'] == 'firstname') $firstname_col = 'firstname';
            if ($col['column_name'] == 'lastname') $lastname_col = 'lastname';
        }
    }
}

// Buscar usuario
$sql_login = "SELECT * FROM $local_table WHERE email = '$e_mail'";
$res = pg_query($local_conn, $sql_login);

if($res){
    if(pg_num_rows($res) > 0){
        $user = pg_fetch_assoc($res);
        $stored_password = $user[$password_column];
        
        // Verificar si es MD5 (32 caracteres hex)
        if(strlen($stored_password) == 32 && ctype_xdigit($stored_password)) {
            if(md5($p_asswd) === $stored_password) {
                // Migrar a bcrypt
                $new_hash = password_hash($p_asswd, PASSWORD_BCRYPT);
                $update_sql = "UPDATE $local_table SET $password_column = '$new_hash' WHERE email = '$e_mail'";
                pg_query($local_conn, $update_sql);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user[$firstname_col] . ' ' . $user[$lastname_col];
                header('Location: index.php');
                exit();
            } else {
                echo "<script>alert('Password incorrecto'); window.location='login.html';</script>";
                exit();
            }
        } else {
            // Password con bcrypt
            if(password_verify($p_asswd, $stored_password)){
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user[$firstname_col] . ' ' . $user[$lastname_col];
                header('Location: index.php');
                exit();
            } else {
                echo "<script>alert('Password incorrecto'); window.location='login.html';</script>";
                exit();
            }
        }
    } else {
        echo "<script>alert('Usuario no encontrado'); window.location='login.html';</script>";
    }
} else {
    echo "<script>alert('Error en la consulta: " . addslashes(pg_last_error($local_conn)) . "'); window.location='login.html';</script>";
}
?>