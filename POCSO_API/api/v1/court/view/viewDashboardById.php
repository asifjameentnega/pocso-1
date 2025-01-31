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

// Handle the request
try {
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($inputData['id']) || empty($inputData['id'])) {
        throw new Exception("Invalid input. 'id' is required.");
    }

    // Sanitize input
    $id = htmlspecialchars(strip_tags($inputData['id']));

    // Define the SQL query with placeholders for dynamic conditions
    $sql = "
SELECT 
    O.ID,
    O.DISTRICT_NAME,
    O.CHILD_GENDER,
    O.POL_STAT,
    O.COMPLAINT_NAME,
    O.DATE_OF_FIR,
    O.FIR_NO,
    C.COURT_FILE_DATE,
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
    COALESCE(C.ORDER_NO, '-') AS ORDER_NO,
    COALESCE(O.CHARGE_SHEET_YES_NO, '-') AS CHARGE_SHEET_YES_NO,
    COALESCE(TO_CHAR(O.CHARGE_SHEET_DATE, 'DD-Mon-YYYY'), '-') AS CHARGE_SHEET_DATE,
    C.INTERIM_ORDER_DATE,
    C.FINAL_AMOUNT,
    C.INTERIM_AMOUNT,
    C.FINAL_ORDER_DATE,
    COALESCE(C.TYPE, '-') AS TYPE,
    COALESCE(C.JUDGEMENT, '-') AS JUDGEMENT,
    CASE 
        WHEN O.REQUEST_STATUS = 'CO' THEN 'Disposed'
        WHEN O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') THEN 'Pending'
        ELSE NULL
    END AS REQUEST_STATUS,
   (CURRENT_DATE - O.CHARGE_SHEET_DATE) AS DURATION,
    COALESCE(C.NAME_OF_COURT, '-') AS NAME_OF_COURT,
    O.INTERIM_YES,
    O.FINAL_YES,
    C.ID AS CID,
    C.INTERIM,
    C.FINAL,
    A.PROCEED_INTERIM AS PROCEED_INTERIM,
    A.PROCEED_FINAL AS PROCEED_FINAL,
    A.INTERIM_PAYMENT_STATUS,
    A.FINAL_PAYMENT_STATUS,
    A.NEW_STATUS,
    A.ID AS AID,
    C.INTERIM,
	C.FINAL,
    CASE
        WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'No' THEN 'Not awarded'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'Yes' AND A.PROCEED_INTERIM IS NULL THEN 'Pending'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND C.INTERIM_AWARDED_YES = 'Yes' AND A.PROCEED_INTERIM IS NOT NULL THEN 'Disbursed'
        ELSE 'Pending'
    END AS Condition_Status,
    CASE
        WHEN C.FINAL IS NOT NULL THEN 'Orders passed'
        WHEN C.FINAL IS NULL THEN 'Pending Trail'
        ELSE 'Pending'
    END AS Condition_Status_1,
    CASE
        WHEN O.WILLINGNESS_COMPENSATION = 'No' THEN 'Not awarded'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND A.PROCEED_FINAL IS NULL THEN 'Pending'
        WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND A.PROCEED_FINAL IS NOT NULL THEN 'Disbursed'
        ELSE 'Pending'
    END AS Condition_Status_2,
    COALESCE(C.INTERIM_AWARDED_YES, '-') AS INTERIM_AWARDED_YES,
    O.NAME_MEDICAL_INSTITUTION,
    O.COMPLAINT_MADE,
    O.DATE_REQUISITION_MEDICAL_EXAMINATION,
    O.DATE_OF_MEDICAL_EXAMINATION_VICTIM,
    O.DATE_OF_INTERIM
FROM 
    TNEGA_OVERALL_T_DUP O
    INNER JOIN TNEGA_JUDGE_LOGIN_T C ON O.ID = C.COURT_ID
    INNER JOIN TNEA_SUPERINTENDENT_T A ON O.ID = A.COURT_ID
    INNER JOIN SIGNUP_T S ON TRIM(O.DISTRICT_NAME) = TRIM(S.DISTRICT_NAME)
WHERE  
   S.ID = :APP_id 
   AND TRIM(S.ROLE) IN ('Court') 
    AND O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C')
ORDER BY 
    O.UPDATED_DATE DESC;
    ";

    // Prepare SQL query
    $stmt = $read_db->prepare($sql);
    $stmt->bindParam(":APP_id", $id, PDO::PARAM_INT);

    // Execute the query
    $stmt->execute();

    // Fetch the results
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare the response
    if ($result) {
        $response = [
            "success" => 1,
            "message" => "Data retrieved successfully",
            "data" => $result
        ];
    } else {
        $response["message"] = "No data found";
    }

} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

// Return the response
echo json_encode($response);

?>
