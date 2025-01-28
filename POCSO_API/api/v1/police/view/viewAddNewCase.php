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


    // SQL query for fetching institutional summary data
    $sql = 'SELECT DISTRICT_NAME,
       POL_STAT,
       YEAR_DAT,
       FIR_NO,
       STATUS,
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
       ID,
       DOC_REF,
       CHILD_GENDER,
       DATE_REQUISITION_MEDICAL_EXAMINATION,
       DATE_OF_MEDICAL_EXAMINATION_VICTIM,
       DATE_OF_INTERIM,
       NAME_MEDICAL_INSTITUTION,
       COMPLAINT_MADE
  from TNEGA_OVERALL_T_DUP  
  
    ';

    // Prepare statement
    $sql_stmt = $read_db->prepare($sql);

    // Bind parameters
   // $sql_stmt->bindParam(':P73_ID', $APP_EMPLOYEE_ID, PDO::PARAM_INT);

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
            $response = ["success" => 0, "message" => "No record found"];
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
