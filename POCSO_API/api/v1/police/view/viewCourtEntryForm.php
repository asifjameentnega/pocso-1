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


    $sql = "SELECT ID,
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
	   'INTERIM',
	   INTERIM_ATTACH_MIMETYPE,
       INTERIM_ATTACH_FILENAME,
	   'FINAL',
	   FINAL_ATTACH_MIMETYPE,
       FINAL_ATTACH_FILENAME,
       NAME_OF_COURT,
       INTERIM_AWARDED_YES
  from TNEGA_JUDGE_LOGIN_T

  
    ";

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
