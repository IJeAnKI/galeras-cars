<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('config/database.php');

// Verificar conexión a PostgreSQL
if (!$local_conn) {
    die("Error de conexión a la base de datos: " . pg_last_error());
}

// Consulta para obtener los usuarios
$sql = "SELECT id, firstname, lastname, email, mobile_phone, created_at FROM users ORDER BY id";
$result = pg_query($local_conn, $sql);

if (!$result) {
    die("Error en la consulta: " . pg_last_error($local_conn));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios - Galeras Cars</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background-color: #4e73df;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            padding: 5px 10px;
            margin: 0 3px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        .btn-edit {
            background-color: #4e73df;
            color: white;
        }
        .btn-delete {
            background-color: #e74a3b;
            color: white;
        }
        .btn-edit:hover {
            background-color: #2e59d9;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
        .no-users {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .volver {
            display: inline-block;
            margin-bottom: 20px;
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 3px;
        }
        .volver:hover {
            background-color: #5a6268;
        }
        .user-photo {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #4e73df;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <a href="index.php" class="volver">Volver al Dashboard</a>

    <h1>Lista de Usuarios Registrados</h1>

    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Foto</th>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Fecha Registro</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (pg_num_rows($result) > 0) {
                while ($row = pg_fetch_assoc($result)) {
                    // Generar iniciales para la foto temporal
                    $initials = strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1));
                    
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td><div class='user-photo'>" . $initials . "</div></td>";
                    echo "<td>" . htmlspecialchars($row['firstname'] . " " . $row['lastname']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td>" . (!empty($row['mobile_phone']) ? htmlspecialchars($row['mobile_phone']) : '—') . "</td>";
                    echo "<td>" . date('d/m/Y', strtotime($row['created_at'])) . "</td>";
                    echo "<td>
                            <a href='edit_user.php?id=" . $row['id'] . "' class='btn btn-edit'> Editar</a>
                            <a href='delete_user.php?id=" . $row['id'] . "' class='btn btn-delete' onclick='return confirm(\"¿Estás seguro de eliminar este usuario?\")'> Eliminar</a>
                            </td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='no-users'>No hay usuarios registrados</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <p style="text-align: center; margin-top: 20px; color: #666;">
        Total de usuarios: <?php echo pg_num_rows($result); ?>
    </p>

</body>
</html>

<?php
pg_close($local_conn);
?>