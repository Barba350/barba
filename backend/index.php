<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$conn = new mysqli(
    getenv("DB_HOST"),
    getenv("DB_USER"),
    getenv("DB_PASS"),
    getenv("DB_NAME")
);

if ($conn->connect_error) {
    echo json_encode(["error" => "No se pudo conectar a la base de datos"]);
    exit;
}

$action = $_GET['action'] ?? 'list';

switch ($action) {

    // Agrega un iPhone nuevo al catálogo
    case 'add':
        $modelo = trim($_POST['modelo'] ?? '');
        $precio = (float) ($_POST['precio'] ?? 0);
        $stock  = (int) ($_POST['stock'] ?? 0);

        if ($modelo === '') {
            echo json_encode(["error" => "El modelo es obligatorio"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO productos (modelo, precio, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("sdi", $modelo, $precio, $stock);
        $stmt->execute();

        echo json_encode(["success" => true]);
        break;

    // Elimina un iPhone del catálogo
    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(["success" => true]);
        break;

    // "Compra" un iPhone: descuenta 1 unidad de stock
    case 'buy':
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $conn->prepare("SELECT stock FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || $row['stock'] < 1) {
            echo json_encode(["error" => "Sin stock disponible"]);
            exit;
        }

        $update = $conn->prepare("UPDATE productos SET stock = stock - 1 WHERE id = ?");
        $update->bind_param("i", $id);
        $update->execute();

        echo json_encode(["success" => true]);
        break;

    // Lista todos los iPhones del catálogo (acción por defecto)
    case 'list':
    default:
        $result = $conn->query("SELECT id, modelo, precio, stock FROM productos ORDER BY id");
        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode($productos);
        break;
}
