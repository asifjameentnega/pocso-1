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

// Initialize the response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    if (!$read_db) {
        throw new Exception("Database connection not established.");
    }

    // Collect and validate input parameters
    $params = json_decode(file_get_contents('php://input'), true);
    $queryParams = [
        'charge_sheet_start_date' => $params['charge_sheet_start_date'] ?? null,
        'charge_sheet_end_date'   => $params['charge_sheet_end_date'] ?? null,
        'fir_start_date'          => $params['fir_start_date'] ?? null,
        'fir_end_date'            => $params['fir_end_date'] ?? null,
        'complaint_name_start'    => $params['complaint_name_start'] ?? null,
        'complaint_name_end'      => $params['complaint_name_end'] ?? null,
        'interim_order_start'     => $params['interim_order_start'] ?? null,
        'interim_order_end'       => $params['interim_order_end'] ?? null,
        'final_order_start'       => $params['final_order_start'] ?? null,
        'final_order_end'         => $params['final_order_end'] ?? null,
        'court_file_start'        => $params['court_file_start'] ?? null,
        'court_file_end'          => $params['court_file_end'] ?? null,
    ];

    // Define the SQL query with placeholders
    $sql = "    SELECT 
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
    C.INTERIM,
    C.FINAL,
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
    (CURRENT_DATE - O.CHARGE_SHEET_DATE) AS DURATION,
    C.NAME_OF_COURT,
    O.INTERIM_YES,
    O.FINAL_YES,
  
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
    DATE_PART('day', CURRENT_DATE - O.DATE_OF_FIR) AS Aging_of_cases,
    DATE_PART('day', O.CHARGE_SHEET_DATE - O.DATE_OF_FIR) AS Aging_of_chargesheet_date
FROM 
    TNEGA_OVERALL_T_DUP O
JOIN 
    TNEGA_JUDGE_LOGIN_T C ON O.ID = C.COURT_ID
JOIN 
    TNEA_SUPERINTENDENT_T S ON O.ID = S.COURT_ID 
WHERE 
    O.REQUEST_STATUS IN ('C', 'CO', 'E', 'S', 'F1')
    AND (O.COMPLAINT_NAME BETWEEN COALESCE(:complaint_name_start, O.COMPLAINT_NAME) AND COALESCE(:complaint_name_end, O.COMPLAINT_NAME) OR O.COMPLAINT_NAME IS NULL)
    AND (O.DATE_OF_FIR BETWEEN COALESCE(:fir_start_date, O.DATE_OF_FIR) AND COALESCE(:fir_end_date, O.DATE_OF_FIR) OR O.DATE_OF_FIR IS NULL)
    AND (O.CHARGE_SHEET_DATE BETWEEN COALESCE(:charge_sheet_start_date, O.CHARGE_SHEET_DATE) AND COALESCE(:charge_sheet_end_date, O.CHARGE_SHEET_DATE) OR O.CHARGE_SHEET_DATE IS NULL)
    AND (C.INTERIM_ORDER_DATE BETWEEN COALESCE(:interim_order_start, C.INTERIM_ORDER_DATE) AND COALESCE(:interim_order_end, C.INTERIM_ORDER_DATE) OR C.INTERIM_ORDER_DATE IS NULL)
    AND (C.FINAL_ORDER_DATE BETWEEN COALESCE(:final_order_start, C.FINAL_ORDER_DATE) AND COALESCE(:final_order_end, C.FINAL_ORDER_DATE) OR C.FINAL_ORDER_DATE IS NULL)
    AND (C.COURT_FILE_DATE BETWEEN COALESCE(:court_file_start, C.COURT_FILE_DATE) AND COALESCE(:court_file_end, C.COURT_FILE_DATE) OR C.COURT_FILE_DATE IS NULL);
";

    // Prepare and execute the SQL statement
    $stmt = $read_db->prepare($sql);
    $stmt->execute($queryParams);

    // Check for results
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
            "message" => "No records found."
        ];
    }
} catch (PDOException $e) {
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => "Database error: " . $e->getMessage()
    ];
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => $e->getMessage()
    ];
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
