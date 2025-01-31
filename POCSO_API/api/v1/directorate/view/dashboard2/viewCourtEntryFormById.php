<?php

// Load dependencies
require_once('../../../../../helper/header.php');
require_once('../../../../../config/read_database.php'); // Ensure this connects to your database

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
    if (!isset($inputData['court_id'])) {
        throw new Exception("Invalid input. Please provide a valid 'court_id'.");
    }

    // Extract input
    $court_id = $inputData['court_id'];

    // Define the SQL query
    $sql = "select ID,
       TYPE,
COURT_FILE_DATE,
       INTERIM_ORDER_DATE,
       ORDER_NO,
       STATUS,
       CREATED_BY,
       CREATED_DATE,
       UPDATED_BY,
       UPDATED_DATE,
       YES_TYPE,
       REMARKS,
       COURT_ID,
       FIR_NO,
       FINAL_ORDER_DATE,
       JUDGEMENT,
       CHANGE_BANK_ACCOUNT,
       BAK_ACCOUNT,
       IFSC_CODE,
       BANK_NAME,
       BRANCH_NAME,
       ACCOUNT_NUMBER,
       REMARKS_BANK,
       INTERIM_AMOUNT,
       final_amount,
	   INTERIM,
	   INTERIM_ATTACH_MIMETYPE,
       INTERIM_ATTACH_FILENAME,
	   FINAL,
	   FINAL_ATTACH_MIMETYPE,
       FINAL_ATTACH_FILENAME,
       NAME_OF_COURT,
       INTERIM_AWARDED_YES
  from TNEGA_JUDGE_LOGIN_T
  WHERE ID = :P86_COURT_ID;
    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':P86_COURT_ID', $court_id);

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
            "message" => "No records found for the provided Court_ID."
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
