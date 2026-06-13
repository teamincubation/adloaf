<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data) {
        $name = htmlspecialchars(strip_tags($data['name'] ?? ''));
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars(strip_tags($data['subject'] ?? ''));
        $service = htmlspecialchars(strip_tags($data['service'] ?? ''));
        $message = htmlspecialchars(strip_tags($data['message'] ?? ''));

        if ($name && $email && $subject && $service && $message) {
            try {
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, service, message) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $subject, $service, $message]);
                
                echo json_encode(['success' => true, 'message' => 'Message stored successfully']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing fields']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
}
?>
