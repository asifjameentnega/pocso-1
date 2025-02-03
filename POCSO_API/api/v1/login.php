<?php
require_once('../../helper/header.php');
// require_once('../../helper/encryptDecrypt.php');
require_once('../../config/read_database.php');

// Constant for date format
define('DATE_FORMAT', 'Y-m-d H:i:s.u');


if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    $data = array("success" => 0, "message" => "Method Not Allowed");
    echo json_encode($data);
    die();
}

$inputData = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($inputData['username']) || empty($inputData['username']) || !isset($inputData['password']) || empty($inputData['password']) ) {
    throw new Exception("Invalid input.");
}

$username = $inputData["username"];
$password = $inputData["password"];

// Decrypt the data
// $decryptedData = isset($_POST['data']) ? decrypt($_POST['data']) : null;
try {
   
    $login_sql = "SELECT * from fn_userlogin(:username, :password)";
    $login_stmt = $read_db->prepare($login_sql);
    $login_stmt->bindParam(':username', $username);
    $login_stmt->bindParam(':password', $password);
    $login_stmt->execute();
    $login_list = $login_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results
    if ($login_list[0]['user_count'] > 0) {
        http_response_code(200);
        $data = array("success" => 1, "message" => "login successfully", 'role' => $login_list[0]['user_role'], 'role_id'=>$login_list[0]['user_id']);
        echo json_encode($data);
    } else {
        http_response_code(200);
        $data = array("success" => 0, "message" => "No Data Found",);
        echo json_encode($data);
    }

} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => 0, "message" => $e->getMessage()]);
    die();
}

