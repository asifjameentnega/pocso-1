<?php

// Load dependencies
require_once('../../../../helper/header.php');
require_once('../../../../config/read_database.php'); // Ensure this connects to your database

// Validate the request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        "success" => 0,
        "message" => "Method Not Allowed. Only POST requests are supported."
    ]);
    exit;
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($inputData['id'])) {
        throw new Exception("Invalid input. Please provide a valid 'id'.");
    }

    // Extract input
    $id = $inputData['id'];

    // Define the SQL query
    $sql = "
        SELECT 
            ID,
            STATUS,
            CREATED_BY,
            CREATED_DATE,
            UPDATED_BY,
            UPDATED_DATE,
            PROCEED_INTERIM,
            PROCEED_ATTACH_MIMETYPE,
            PROCEED_ATTACH_FILENAME,
            COURT_ID,
            SENIORITY_LEVEL,
            REMARKS,
            FIR_NO,
            COURT_FILE_NO,
            PROCEED_FINAL,
            NEFT_ATTACH_MIMETYPE,
            NEFT_ATTACH_FILENAME,
            CHANGE_BANK_ACCOUNT,
            NEW_STATUS,
            BAK_ACCOUNT,
            BANK_NAME,
            BRANCH_NAME,
            ACCOUNT_NUMBER,
            IFSC_CODE,
            REMARKS_BANK,
            ACC_HOLDER_NAME,
            FINAL_PAYMENT_STATUS,
            INTERIM_PAYMENT_STATUS
        FROM 
            TNEA_SUPERINTENDENT_T
        WHERE 
            ID = :P56_ID
    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':P56_ID', $id);

    // Execute the query
    $stmt->execute();

    // Check if data is returned
    if ($stmt->rowCount() > 0) {
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = [
            "success" => 1,
            "message" => "Data fetched successfully",
            "data" => $data
        ];
    } else {
        $response = [
            "success" => 0,
            "message" => "No records found for the provided ID."
        ];
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500); // Internal Server Error
    $response = [
        "success" => 0,
        "message" => $e->getMessage()
    ];
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);
