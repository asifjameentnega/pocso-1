<?php
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

define('DATE_FORMAT', 'Y-m-d H:i:s.u');

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

    // Extract and validate required fields
    $requiredFields = ['id', 'COMPLAINT_COPY_FILENAME', 'COMPLAINT_COPY_MIMETYPE'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($inputData[$field])) {
            $missingFields[] = $field;
        }
    }

    // Throw an error if any required field is missing
    if (!empty($missingFields)) {
        throw new Exception("Invalid input. The following fields are required and cannot be null: " . implode(', ', $missingFields));
    }

    // Assign validated input data to variables
    $APP_EMPLOYEE_ID = $inputData['id'];
    $COMPLAINT_COPY_FILENAME = $inputData['COMPLAINT_COPY_FILENAME'];
    $COMPLAINT_COPY_MIMETYPE = $inputData['COMPLAINT_COPY_MIMETYPE'];
    $COMPLAINT_COPY = $inputData['COMPLAINT_COPY'] ?? null; // Optional field

    // Prepare SQL query
    $sql = "UPDATE TNEGA_OVERALL_T_DUP
            SET COMPLAINT_COPY = :P50_COMPLAINT_COPY,
                COMPLAINT_COPY_FILENAME = :P50_COMPLAINT_COPY_FILENAME,
                COMPLAINT_COPY_MIMETYPE = :P50_COMPLAINT_COPY_MIMETYPE
            WHERE ID = :P50_ID";

    $sql_stmt = $write_db->prepare($sql);

    // Bind parameters
    $sql_stmt->bindParam(':P50_ID', $APP_EMPLOYEE_ID, PDO::PARAM_INT);
    $sql_stmt->bindParam(':P50_COMPLAINT_COPY', $COMPLAINT_COPY, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P50_COMPLAINT_COPY_FILENAME', $COMPLAINT_COPY_FILENAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P50_COMPLAINT_COPY_MIMETYPE', $COMPLAINT_COPY_MIMETYPE, PDO::PARAM_STR);

    // Execute the query
    if ($sql_stmt->execute()) {
        if ($sql_stmt->rowCount() > 0) {
            $response = ["success" => 1, "message" => "Record updated successfully"];
        } else {
            $response = ["success" => 0, "message" => "No matching record found to update"];
        }
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
