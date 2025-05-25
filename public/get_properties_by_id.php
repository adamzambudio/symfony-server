<?php
header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json, charset=utf-8');

$host = 'localhost';
$dbname = 'tfg';
$username = 'root';
$password = '';

// Ruta base de imágenes
$baseImagePath = 'img/';

// Validar que venga el ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetro ID no válido']);
    exit;
}

$id = intval($_GET['id']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta por ID con sus imágenes
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.title, p.description, p.address, p.price, i.url AS image_url
        FROM 
            property p
        LEFT JOIN 
            image i ON p.id = i.property_id
        WHERE 
            p.id = :id
    ");
    $stmt->execute(['id' => $id]);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        http_response_code(404);
        echo json_encode(['error' => 'Propiedad no encontrada']);
        exit;
    }

    // Formatear resultado
    $property = [
        'id' => $results[0]['id'],
        'title' => $results[0]['title'],
        'description' => $results[0]['description'],
        'address' => $results[0]['address'],
        'price' => $results[0]['price'],
        'image' => []
    ];

    foreach ($results as $row) {
        if (!empty($row['image_url'])) {
            $property['image'][] = $baseImagePath . $row['image_url'];
        }
    }

    echo json_encode($property);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
