<?php
$host = 'db';
$user = 'root';
$pass = 'root_password';
$db = 'db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function jsonResponseSuccess($data) {
    ob_clean();
    header_remove();
    header("Content-type: application/json; charset=utf-8");
    http_response_code(200);

    echo json_encode($data);
    exit();
}

if (isset($_GET['random-id'])) {
    $rand = rand(1, 4);
    jsonResponseSuccess([ 'success' => true, 'randomId' => $rand ]);
}

if (isset($_GET['image_id'])) {
    $stmt = $conn->prepare("SELECT SUM(view_count) as views from `logs` WHERE image_mark = ?");
    $stmt->bind_param('i', $imageId);
    $stmt->execute();

    $stmt->bind_result($result);
    $stmt->fetch();

    jsonResponseSuccess([ 'success' => true, 'views' => $result->views ]);
}

if (isset($_POST['image_id'])) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $imageId = (int) $_POST['image_id'];

    $stmt = $conn->prepare("SELECT id, view_count as views from `logs` WHERE ip_address = ? AND user_agent = ? AND image_mark = ?");
    $stmt->bind_param('ssi', $ip, $ua, $imageId);
    $stmt->execute();

    $stmt->bind_result($result);
    $stmt->fetch();

    if ($result->id) {
        $result = $conn->query("UPDATE `logs` SET view_date = CURRENT_TIMESTAMP, view_count = 2 WHERE id = " . $result->id);
    } else {
        $stmt = $conn->prepare("INSERT INTO `logs` (ip_address, user_agent, image_mark, view_date, view_count) 
                                        VALUES (?, ?, ?, CURRENT_TIMESTAMP, 1)");
        $stmt->bind_param('ssi', $ip, $ua, $imageId);
        $stmt->execute();
    }

    jsonResponseSuccess([ 'success' => true ]);
}
