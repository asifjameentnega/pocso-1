<?php
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

// Constants
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
    $requiredFields = [
        'FINAL_AMOUNT',
        'FINAL_ORDER_DATE',
        'ID'
      
    ];

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

    // Extract input data into variables
    $FINAL_AMOUNT = $inputData['FINAL_AMOUNT'];
    $FINAL_ORDER_DATE = $inputData['FINAL_ORDER_DATE'];
    $ID = $inputData['ID'];
   

    if ($FINAL_AMOUNT && $FINAL_ORDER_DATE ) {
        // Prepare the SQL query
        $sql = "UPDATE TNEGA_JUDGE_LOGIN_T
        SET FINAL_AMOUNT = :P53_FINAL_AMOUNT,
            FINAL_ORDER_DATE = :P53_FINAL_ORDER_DATE
        WHERE COURT_ID = :P53_ID 
            AND INTERIM_AMOUNT IS NOT NULL 
            AND INTERIM_ORDER_DATE IS NOT NULL";


        // Prepare the SQL statement
        $sql_stmt = $write_db->prepare($sql);

        // Bind parameters
        $sql_stmt->bindParam(':P53_FINAL_AMOUNT', $FINAL_AMOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':P53_FINAL_ORDER_DATE', $FINAL_ORDER_DATE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':P53_ID', $ID, PDO::PARAM_STR);

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
    } else {
        $response = ["success" => 0, "message" => "FINAL_AMOUNT or FINAL_ORDER_DATE condition not met, no update performed."];
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
