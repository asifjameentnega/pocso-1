<?php

// Load dependencies
require_once('../../../../../helper/header.php');
require_once('../../../../../config/read_database.php'); // Ensure this connects to your database

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

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
    // Check if database connection exists
    if (!isset($read_db) || !$read_db) {
        throw new Exception("Database connection failed.");
    }

    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($inputData['id'])) {
        throw new Exception("Invalid input. Please provide a valid 'id'.");
    }

    // Extract input
    $id = $inputData['id'];

    // Define the SQL query (Removed duplicate ID selection)
    $sql = "SELECT
        ID, DISTRICT_NAME,
        POL_STAT,
        YEAR_DAT,
        FIR_NO,
        'STATUS',
        CREATED_BY,
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
        COURT_FILE_NO,
        TYPE_OF_ORDER,
        INTERIM_DATE,
        FINAL_DATE,
        AMOUNT_OF_COMPENSATION,
        COURT_ATTACH,
        COURT_ATTACH_MIMETYPE,
        COURT_ATTACH_FILENAME,
        COMPLAINT_NAME,
        REQUEST_STATUS,
        CHARGE_SHEET_YES_NO,
        CHARGE_SHEET_DATE,
        WILLINGNESS_COMPENSATION,
        INTERIM_YES,
        FINAL_YES,
        COMPLAINT_COPY,
        COMPLAINT_COPY_MIMETYPE,
        COMPLAINT_COPY_FILENAME,
        WHO_APPLYING,
        WHO_NAME,
        WHO_RELATIONSHIP,
        WHO_DATE,
        BANK_DETAILS,
        CHILD_GENDER,
        DATE_REQUISITION_MEDICAL_EXAMINATION,
        DATE_OF_MEDICAL_EXAMINATION_VICTIM,
        DATE_OF_INTERIM,
        NAME_MEDICAL_INSTITUTION,
        COMPLAINT_MADE
    FROM TNEGA_OVERALL_T_DUP
    WHERE ID = :P86_id
    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':P86_id', $id, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Check if data is returned
    if ($stmt->rowCount() > 0) {
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all matching records
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
    // Log error for debugging
   // error_log("Error: " . $e->getMessage());

    // Handle exceptions
    http_response_code(400); // Internal Server Error
    $response = [
        "success" => 0,
        "message" => "Internal Server Error"
    ];
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);
+