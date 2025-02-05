<?php

// Load dependencies
require_once('../../../../helper/header.php');
require_once('../../../../config/read_database.php');

// Validate the request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
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
    if (!isset($inputData['id']) || empty($inputData['id'])) {
        throw new Exception("Invalid input. 'id' is required.");
    }

    // Sanitize input
    $employee_id = htmlspecialchars(strip_tags($inputData['id']));

    // Validate the rest of the parameters if present
    // Define the full SQL query
    $sql = "SELECT DISTINCT
    O.ID,
    O.DISTRICT_NAME,
    O.POL_STAT,
    O.CHILD_GENDER,
    O.NAME_MEDICAL_INSTITUTION,
    O.COMPLAINT_MADE,
    O.DATE_REQUISITION_MEDICAL_EXAMINATION,
    O.DATE_OF_MEDICAL_EXAMINATION_VICTIM,
    O.DATE_OF_INTERIM,
    O.COMPLAINT_NAME,
    O.DATE_OF_FIR,
    O.FIR_NO,
    C.COURT_FILE_DATE,
    C.INTERIM_AWARDED_YES,
    O.COMPLAINT_COPY_MIMETYPE,
    O.COMPLAINT_COPY_FILENAME,
    O.NAME_PARENT,
    O.WILLINGNESS_COMPENSATION,
    COALESCE(O.WHO_NAME, '-') AS WHO_NAME,
    COALESCE(O.WHO_RELATIONSHIP, '-') AS WHO_RELATIONSHIP,
    COALESCE(TO_CHAR(O.WHO_DATE, 'DD-Mon-YYYY'), '-') AS WHO_DATE,
    COALESCE(O.ACCOUNT_NUMBER, '-') AS ACCOUNT_NUMBER,
    COALESCE(O.BANK_NAME, '-') AS BANK_NAME,
    COALESCE(O.BRANCH_NAME, '-') AS BRANCH_NAME,
    COALESCE(O.ACCOUNT_HOLDER_NAME, '-') AS ACCOUNT_HOLDER_NAME,
    COALESCE(O.IFSC_CODE, '-') AS IFSC_CODE,
    C.ORDER_NO,
    O.CHARGE_SHEET_YES_NO,
    COALESCE(TO_CHAR(O.CHARGE_SHEET_DATE, 'DD-Mon-YYYY'), '-') AS CHARGE_SHEET_DATE,
    C.INTERIM_ORDER_DATE,
    C.FINAL_AMOUNT,
    C.INTERIM_AMOUNT,
    C.FINAL_ORDER_DATE,
    C.JUDGEMENT,
    CASE 
        WHEN O.REQUEST_STATUS = 'CO' THEN 'Disposed'
        WHEN O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN 'Pending'
        WHEN O.REQUEST_STATUS = 'C' THEN 'Disposed'
    END AS Payment_Status,
    CURRENT_DATE - O.CHARGE_SHEET_DATE AS DURATION,
    C.NAME_OF_COURT,
    O.INTERIM_YES,
    O.FINAL_YES,
    C.INTERIM,
    C.FINAL,
    CASE
        WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AMOUNT IS NOT NULL AND S.PROCEED_INTERIM IS NULL THEN 'Pending'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AMOUNT IS NOT NULL AND S.PROCEED_INTERIM IS NOT NULL THEN 'Disbursed'
        ELSE 'Other Condition'
    END AS Condition_Status,
    CASE
        WHEN C.FINAL IS NOT NULL THEN 'Orders passed'
        WHEN C.FINAL IS NULL THEN 'Pending Trail'
        ELSE 'Other Condition'
    END AS Condition_Status_1,
    CASE
        WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NULL THEN 'Pending'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NOT NULL THEN 'Disbursed'
        ELSE 'Other Condition'
    END AS Condition_Status_2,
   CURRENT_DATE - O.DATE_OF_FIR AS Aging_of_cases,
O.CHARGE_SHEET_DATE - O.DATE_OF_FIR AS Aging_of_chargesheet_date

FROM 
    TNEGA_OVERALL_T_DUP O
JOIN 
    TNEGA_JUDGE_LOGIN_T C ON O.ID = C.COURT_ID
JOIN 
    TNEA_SUPERINTENDENT_T S ON O.ID = S.COURT_ID 
JOIN 
    SIGNUP_T a ON a.mobile_number = O.district_name 
WHERE 
    O.REQUEST_STATUS IN ('C','CO','E','S','F1') 
    AND a.role IN ('Admin') 
    AND a.district_name IN ('District') 
    AND a.ID = :APP_EMPLOYEE_ID

";


    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':APP_EMPLOYEE_ID', $employee_id);
   

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
            "message" => "No records found for the provided EMPLOYEE_ID."
        ];
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => $e->getMessage()
    ];

    // Log error (optional)
  
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);
