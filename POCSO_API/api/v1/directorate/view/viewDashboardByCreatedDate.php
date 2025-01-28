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
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input data
    if (!isset($inputData['from_date'])) {
        throw new Exception("Invalid input. Please provide a valid 'from_date'.");
    }

    $P1_FROM_DATE1 = $inputData['from_date']; // Input date

    // SQL query for fetching institutional summary data
    $sql = "
        SELECT 
            ROW_NUMBER() OVER (ORDER BY O.UPDATED_DATE DESC) AS Sl_No, 
            A.*
        FROM (
            SELECT 
                O.ID,
                O.DISTRICT_NAME,
                O.POL_STAT,
                O.CHILD_GENDER,
                O.Created_Date,
                O.COMPLAINT_NAME,
                O.DATE_OF_FIR,
                O.FIR_NO,
                O.COMPLAINT_COPY,
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
                C.id AS cid,
                C.INTERIM_ORDER_DATE,
                C.FINAL_AMOUNT,
                C.INTERIM_AMOUNT,
                C.TYPE,
                C.FINAL_ORDER_DATE,
                C.JUDGEMENT,
                CASE 
                    WHEN O.REQUEST_STATUS = 'CO' THEN 'Paid'
                    WHEN O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') THEN 'Pending'
                    ELSE NULL
                END AS Payment_Status,
                CURRENT_DATE - O.CHARGE_SHEET_DATE AS DURATION,
                C.INTERIM,
                C.FINAL,
                S.PROCEED_INTERIM,
                S.PROCEED_FINAL,
                S.NEW_STATUS,
                S.INTERIM_PAYMENT_STATUS,
                S.FINAL_PAYMENT_STATUS,
                C.INTERIM,
                C.FINAL,
                S.Proceed_interim,
                S.Proceed_final,
                S.court_id,
                S.id AS sid,
                C.INTERIM_AWARDED_YES,
                O.INTERIM_YES,
                O.FINAL_YES,
                C.NAME_OF_COURT,
                CASE
                    WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
                    WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'No' THEN 'Not awarded'
                    WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'Yes' AND S.PROCEED_INTERIM IS NULL THEN 'Pending'
                    WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'Yes' AND S.PROCEED_INTERIM IS NOT NULL THEN 'Disbursed'
                    ELSE 'Pending'
                END AS Condition_Status,
                CASE
                    WHEN C.FINAL IS NOT NULL THEN 'Orders passed'
                    WHEN C.FINAL IS NULL THEN 'Pending Trail'
                    ELSE 'Pending'
                END AS Condition_Status_1,
                CASE
                    WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
                    WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NULL THEN 'Pending'
                    WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NOT NULL THEN 'Disbursed'
                    ELSE 'Pending'
                END AS Condition_Status_2,
                CURRENT_DATE - O.DATE_OF_FIR AS Aging_of_cases,
                O.CHARGE_SHEET_DATE - O.DATE_OF_FIR AS Aging_of_chargesheet_date,
                O.NAME_MEDICAL_INSTITUTION,
                O.COMPLAINT_MADE,
                O.DATE_REQUISITION_MEDICAL_EXAMINATION,
                O.DATE_OF_MEDICAL_EXAMINATION_VICTIM,
                O.DATE_OF_INTERIM
            FROM 
                TNEGA_OVERALL_T_dup O
            INNER JOIN TNEGA_JUDGE_LOGIN_T C ON O.ID = C.COURT_ID
            INNER JOIN TNEA_SUPERINTENDENT_T S ON O.ID = S.COURT_ID
            WHERE 
                O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C')
                AND (O.CREATED_DATE = COALESCE(:P1_FROM_DATE1, O.CREATED_DATE))
            ORDER BY 
                O.UPDATED_DATE DESC
        ) A
    ";

    // Prepare statement
    $sql_stmt = $read_db->prepare($sql);

    // Bind parameters
    $sql_stmt->bindParam(':P1_FROM_DATE1', $P1_FROM_DATE1);

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
            $response = ["success" => 0, "message" => "No record found for the given criteria"];
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
