<?php
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    die();
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    // Decode input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate JSON decoding
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input.");
    }

    // Extract and validate required field
    if (empty($inputData['COURT_ID'])) {
        throw new Exception("Invalid input. 'COURT_ID' is required and cannot be null.");
    }

    // Assign validated input data to a variable
    $COURT_ID = $inputData['COURT_ID'];

    // Prepare SQL query
    $sql = "INSERT INTO TNEA_SUPERINTENDENT_T (COURT_ID)
                           VALUES (:P50_ID);
";

    $sql_stmt = $write_db->prepare($sql);

    // Bind parameter
    $sql_stmt->bindParam(':P50_ID', $COURT_ID, PDO::PARAM_STR);

    // Execute the query
    if ($sql_stmt->execute()) {
        $response = [
            "success" => 1,
            "message" => "Record inserted successfully",
        ];
    } else {
        throw new Exception("Database execution error.");
    }
} catch (Exception $e) {
    // Log and handle exceptions
    error_log($e->getMessage());
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => "Internal server error.",
        "error" => $e->getMessage() // For debugging, remove in production
    ];
}

// Output response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
