<?php

// Load dependencies
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/read_database.php');

// Define constants
define('DATE_FORMAT', 'Y-m-d H:i:s.u');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "success" => 0,
        "message" => "Method Not Allowed. Only POST requests are supported."
    ]);
    die();
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input data
    if (!isset($inputData['user_id']) || !is_numeric($inputData['user_id'])) {
        throw new Exception("Invalid input. Please provide a valid numeric 'user_id'.");
    }

    $APP_EMPLOYEE_ID = (int)$inputData['user_id'];

    // SQL query for fetching institutional summary data
    $sql = '
        SELECT 
            ID,
            COURT_ID,
            REMARKS,
            SIGNED_PROCEED,
            SIGNED_MIMETYPE,
            SIGNED_FILENAME,
            NEW_STATUS
        FROM 
            TNEA_SIGNED_DOCUMNETS_T
        WHERE 
            ID = :P73_ID
    ';

    // Prepare statement
    $sql_stmt = $read_db->prepare($sql);

    // Bind parameters
    $sql_stmt->bindParam(':P73_ID', $APP_EMPLOYEE_ID, PDO::PARAM_INT);

    // Execute query
    if ($sql_stmt->execute()) {
        // Check if rows are returned
        if ($sql_stmt->rowCount() > 0) {
            // Fetch data
            $data = $sql_stmt->fetchAll(PDO::FETCH_ASSOC);
            http_response_code(200); // OK
            $response = [
                "success" => 1,
                "message" => "Record fetched successfully",
                "data" => $data
            ];
        } else {
            // No record found
            http_response_code(404); // Not Found
            $response = ["success" => 0, "message" => "No record found for the given user_id"];
        }
    } else {
        // Query execution error
        throw new Exception("Database execution error.");
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500); // Internal Server Error
    $response = [
        "success" => 0,
        "message" => $e->getMessage()
    ];
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);
