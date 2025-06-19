<?php
include 'includes/config.php';

header('Content-Type: application/json');

if (isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    
    try {
        $stmt = $pdo->prepare("SELECT ID_CLIENTS FROM CLIENTS WHERE PHONE_NUMBER = ?");
        $stmt->execute([$phone]);
        $client = $stmt->fetch();
        
        echo json_encode(['exists' => $client ? true : false]);
    } catch (PDOException $e) {
        echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['exists' => false, 'error' => 'No phone number provided']);
}
?>
