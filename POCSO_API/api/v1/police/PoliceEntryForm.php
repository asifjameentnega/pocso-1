<?php

// Load dependencies
require_once('../../../helper/header.php');
require_once('../../../helper/encryptDecrypt.php');
require_once('../../../config/read_database.php');

// Define constants
define('DATE_FORMAT', 'Y-m-d H:i:s.u');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    die();
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
  
    $inputData = json_decode(file_get_contents("php://input"), true);
   
    $appEmployeeId = isset($inputData['id']) ? ($inputData['id']) : null;

  if (empty($appEmployeeId)) {
        throw new Exception("Invalid request. Missing required parameter: id.");
    }


    // SQL query for fetching institutional summary data
    $sql= 'SELECT 
    ID,
    DISTRICT_NAME,
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
    BANK_DETAILS,
    CHILD_GENDER,
    DATE_REQUISITION_MEDICAL_EXAMINATION,
    DATE_OF_MEDICAL_EXAMINATION_VICTIM,
    DATE_OF_INTERIM,
    NAME_MEDICAL_INSTITUTION,
    COMPLAINT_MADE
FROM 
    TNEGA_OVERALL_T_DUP
WHERE 
    ID = :id
    ';

    // Prepare statement
    $sql_stmt = $read_db->prepare($sql);
    $sql_stmt->bindParam(':id', $appEmployeeId, PDO::PARAM_STR);

    // Execute query
    if($sql_stmt->execute()){
        $result = $sql_stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($result) > 0) {
            http_response_code(200); // OK
            $response = [
                "success" => 1,
                "message" => "Details fetched successfully",
                "data" => $result
            ];
            
        } else {
            http_response_code(200); // No Content
            $response = ["success" => 2, "message" => "No records found"];
         
        }
    }else{
        http_response_code(400); // Internal Server Error
        $response = ["success" => 0, "message" => "Problem in executing the query in db"];
    }
    

    // Check and return results
    
    
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response = ["success" => 0, "message" => $e->getMessage()];
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);