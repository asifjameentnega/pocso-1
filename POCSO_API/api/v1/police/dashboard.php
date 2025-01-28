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
   
     $APP_EMPLOYEE_ID = isset($inputData['employee_id']) ? ($inputData['employee_id']) : null;

  if (empty($APP_EMPLOYEE_ID)) {
        throw new Exception("Invalid request. Missing required parameter: employee_id.");
    }


    // SQL query for fetching institutional summary data
    $sql= 'SELECT 
            O.ID,
            O.COMPLAINT_NAME,
            O.DO_REF,
            O.COMPLAINT_COPY,
            O.COMPLAINT_COPY_MIMETYPE,
            O.COMPLAINT_COPY_FILENAME,
            O.FIR_NO,
            O.DATE_OF_FIR,
            O.DOC_REF,
            O.fir_attach,
            O.FIR_ATTACH_MIMETYPE,
            O.FIR_ATTACH_FILENAME,
            O.CHARGE_SHEET_YES_NO,
            COALESCE(TO_CHAR(O.CHARGE_SHEET_DATE, \'DD-Mon-YYYY\'), \'-\') AS CHARGE_SHEET_DATE,
            O.CHILD_AGE,
            O.NAME_PARENT,
            O.WILLINGNESS_COMPENSATION,
            COALESCE(O.WHO_NAME, \'-\') AS WHO_NAME,
            COALESCE(O.WHO_RELATIONSHIP, \'-\') AS WHO_RELATIONSHIP,
            COALESCE(TO_CHAR(O.WHO_DATE, \'DD-Mon-YYYY\'), \'-\') AS WHO_DATE,
            COALESCE(O.ACCOUNT_HOLDER_NAME, \'-\') AS ACCOUNT_HOLDER_NAME,
            COALESCE(O.ACCOUNT_NUMBER, \'-\') AS ACCOUNT_NUMBER,
            COALESCE(O.BANK_NAME, \'-\') AS BANK_NAME,
            COALESCE(O.BRANCH_NAME, \'-\') AS BRANCH_NAME,
            COALESCE(O.IFSC_CODE, \'-\') AS IFSC_CODE,
            O.DISTRICT_NAME,
            O.POL_STAT,
            O.YEAR_DAT,
            O.CHILD_GENDER,
            O.STATUS,
            O.CREATED_BY,
            O.CREATED_DATE,
            O.UPDATED_DATE,
            O.UPDATED_BY,
            O.CHILD_NAME,
            O.AGE,
            S.DISTRICT_NAME AS DS,
            CASE 
                WHEN O.REQUEST_STATUS = \'CO\' THEN \'Paid\'
                WHEN O.REQUEST_STATUS IN (\'E\', \'S\', \'F1\', \'C\') THEN \'Pending\'
                ELSE NULL
            END AS Payment_Status,
            \'<span aria-hidden="true" class="fa fa-download-alt"></span>\' AS COPY,
            \'<span aria-hidden="true" class="fa fa-download-alt"></span>\' AS LINK,
            CASE
                WHEN O.WILLINGNESS_COMPENSATION = \'No\' THEN \'Not awarded\'
                WHEN O.WILLINGNESS_COMPENSATION = \'Yes\' AND C.INTERIM_AWARDED_YES = \'No\' THEN \'Not awarded\'
                WHEN O.WILLINGNESS_COMPENSATION = \'Yes\' AND C.INTERIM_AWARDED_YES = \'Yes\' AND A.PROCEED_INTERIM IS NULL THEN \'Pending\'
                WHEN O.WILLINGNESS_COMPENSATION = \'Yes\' AND C.INTERIM_AWARDED_YES = \'Yes\' AND A.PROCEED_INTERIM IS NOT NULL THEN \'Disbursed\'
                ELSE \'Pending\'
            END AS Condition_Status,
            CASE
                WHEN C.FINAL_REF IS NOT NULL THEN \'Orders passed\'
                WHEN C.FINAL_REF IS NULL THEN \'Pending Trail\'
                ELSE \'Pending\'
            END AS Condition_Status_1,
            CASE
                WHEN O.WILLINGNESS_COMPENSATION = \'No\' THEN \'Not awarded\'
                WHEN O.WILLINGNESS_COMPENSATION = \'Yes\' AND A.PROCEED_FINAL IS NULL THEN \'Pending\'
                WHEN O.WILLINGNESS_COMPENSATION = \'Yes\' AND A.PROCEED_FINAL IS NOT NULL THEN \'Disbursed\'
                ELSE \'Pending\'
            END AS Condition_Status_2,
            COALESCE(C.INTERIM_AWARDED_YES, \'-\') AS INTERIM_AWARDED_YES,
            O.NAME_MEDICAL_INSTITUTION,
            O.COMPLAINT_MADE,
            O.DATE_REQUISITION_MEDICAL_EXAMINATION,
            O.DATE_OF_MEDICAL_EXAMINATION_VICTIM,
            O.DATE_OF_INTERIM
        FROM 
            TNEGA_OVERALL_T_DUP O
        INNER JOIN 
            TNEGA_JUDGE_LOGIN_T C ON O.ID = C.COURT_ID
        INNER JOIN 
            TNEA_SUPERINTENDENT_T A ON O.ID = A.COURT_ID
        INNER JOIN 
            SIGNUP_T S ON TRIM(O.POL_STAT) = TRIM(S.NAME)
        WHERE  
            S.ID = :APP_EMPLOYEE_ID 
            AND TRIM(S.ROLE) IN (\'Police\')  
        ORDER BY 
            O.UPDATED_DATE DESC;
    ';

    // Prepare statement
    $sql_stmt = $read_db->prepare($sql);
    $sql_stmt->bindParam(':APP_EMPLOYEE_ID', $appEmployeeId, PDO::PARAM_STR);

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