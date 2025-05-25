<?php

header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json, charset=utf-8');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'tfg';
$username = 'root';
$password = '';

// Ruta base de las imágenes
$baseImagePath = 'img/';

// Conexión a la base de datos
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Consulta: propiedades con imágenes
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.title, p.description, p.address, p.price, i.url AS image_url
        FROM 
            property p
        LEFT JOIN 
            image i ON p.id = i.property_id
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por propiedad
    $properties = [];
    foreach ($results as $row) {
        $id = $row['id'];

        if (!isset($properties[$id])) {
            $properties[$id] = [
                'id' => $id,
                'title' => $row['title'],
                'description' => $row['description'],
                'address' => $row['address'],
                'price' => $row['price'],
                'image' => []
            ];
        }

        if (!empty($row['image_url'])) {
            // Construir ruta completa
            $properties[$id]['image'][] = $baseImagePath . $row['image_url'];
        }
    }

    // Respuesta en JSON
    header('Content-Type: application/json');
    echo json_encode(array_values($properties));
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con la base de datos: ' . $e->getMessage()]);
}
?>
