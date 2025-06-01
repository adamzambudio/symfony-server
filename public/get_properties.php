<?php

header('Access-Control-Allow-Headers: Access-Control-Allow-Origin, Content-Type');
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json, charset=utf-8');

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'tfgg';
$username = 'root';
$password = '';

// Ruta base de las imágenes
$baseImagePath = 'img/';

// Leer parámetros GET para filtros
$city = isset($_GET['city']) ? $_GET['city'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;
$min = isset($_GET['min']) ? floatval($_GET['min']) : null;
$max = isset($_GET['max']) ? floatval($_GET['max']) : null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Construir consulta base con WHERE 1=1 para facilitar concatenar condiciones
    $sql = "SELECT 
                p.id, p.title, p.description, p.address, p.price, p.city, p.type, p.cp, i.url AS image_url
            FROM 
                property p
            LEFT JOIN 
                image i ON p.id = i.property_id
            WHERE 1=1 ";

    $params = [];

    if ($city) {
        $sql .= " AND LOWER(p.city) = LOWER(:city)";
        $params[':city'] = $city;
    }

    if ($type) {
        $sql .= " AND LOWER(p.type) = LOWER(:type)";
        $params[':type'] = $type;
    }

    if ($min !== null) {
        $sql .= " AND p.price >= :min";
        $params[':min'] = $min;
    }

    if ($max !== null) {
        $sql .= " AND p.price <= :max";
        $params[':max'] = $max;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar imágenes por propiedad
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
                'city' => $row['city'],
                'type' => $row['type'],
                'cp' => $row['cp'],
                'image' => []
            ];
        }

        if (!empty($row['image_url'])) {
            $properties[$id]['image'][] = $baseImagePath . $row['image_url'];
        }
    }

    echo json_encode(array_values($properties));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con la base de datos: ' . $e->getMessage()]);
}
