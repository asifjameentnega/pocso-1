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
            DISTRICT_NAME,
            POL_STAT,
            YEAR_DAT,
            FIR_NO,
            STATUS,
            CREATED_BY,
            BANK_DETAILS,
            CREATED_DATE,
            UPDATED_DATE,
            UPDATED_BY,
            SENT_TO,
            DATE_OF_FIR,
            CHILD_NAME,
            CHILD_AGE,
            AGE,
            NAME_PARENT,
            ACCOUNT_NUMBER,
            BANK_NAME,
            BRANCH_NAME,
            ACCOUNT_HOLDER_NAME,
            IFSC_CODE,
            FIR_ATTACH,
            FIR_ATTACH_MIMETYPE,
            FIR_ATTACH_FILENAME,
            COMPLAINT_NAME,
            CHARGE_SHEET_YES_NO,
            CHARGE_SHEET_DATE,
            WILLINGNESS_COMPENSATION,
            COMPLAINT_COPY,
            COMPLAINT_COPY_MIMETYPE,
            COMPLAINT_COPY_FILENAME,
            WHO_APPLYING,
            WHO_NAME,
            WHO_RELATIONSHIP,
            WHO_DATE,
            REQUEST_STATUS,
            CHILD_GENDER,
            DATE_REQUISITION_MEDICAL_EXAMINATION,
            DATE_OF_MEDICAL_EXAMINATION_VICTIM,
            DATE_OF_INTERIM,
            NAME_MEDICAL_INSTITUTION,
            COMPLAINT_MADE
        FROM 
            TNEGA_OVERALL_T_DUP
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
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
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
