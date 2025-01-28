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
   
    // Define the full SQL query with placeholders for the dynamic conditions
    $sql = " SELECT 
    ROW_NUMBER() OVER (ORDER BY A.DISTRICT_NAME) AS ROWNUM, 
    A.*, 
    ROUND(
        COALESCE(
            (A.No_of_cases_Convicted / NULLIF(A.Final_Compensation_No_of_cases_awarded, 0) * 100), 
            0
        ), 
        2
    ) AS Conviction_rate,
    (COALESCE(A.Interim_Compensation_Pending_Amount, 0) + COALESCE(A.Final_Compensation_Pending_Amount, 0)) AS TOTAL
FROM (
    SELECT  
        O.DISTRICT_NAME,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' THEN 1 END) AS WILLINGNESS_COMPENSATION, 
        COUNT(*) AS No_of_POCSO_cases_filed,
        COUNT(CASE WHEN O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN 1 END) AS Case_Status_Pending,
        COUNT(CASE WHEN O.REQUEST_STATUS IN ('CO', 'C') THEN 1 END) AS Case_Status_Completed,
        COALESCE(COUNT(CASE WHEN J.JUDGEMENT = 'Acquittal' THEN 1 END), 0) AS No_of_cases_Acquitted,
        COALESCE(COUNT(CASE WHEN J.JUDGEMENT = 'Convicted' THEN 1 END), 0) AS No_of_cases_Convicted,
        COUNT(CASE WHEN J.JUDGEMENT = 'Others' THEN 1 END) AS Other_Disposals,
        COUNT(CASE WHEN J.INTERIM_AMOUNT IS NOT NULL THEN 1 END) AS Interim_Compensation_No_of_cases_awarded,
        COALESCE(SUM(J.INTERIM_AMOUNT), 0) AS Interim_Compensation_awarded_Amount,
        COUNT(CASE WHEN J.INTERIM_AMOUNT IS NOT NULL THEN 1 END) AS Interim_Compensation_No_of_cases_Disbursed,
        COALESCE(SUM(CASE WHEN J.INTERIM_AMOUNT IS NOT NULL THEN COALESCE(J.INTERIM_AMOUNT, 0) END), 0) AS Interim_Compensation_Disbursed_Amount,
        COUNT(CASE WHEN J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN 1 END) AS Interim_Compensation_No_of_cases_Pending,
        COALESCE(SUM(CASE WHEN J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN COALESCE(J.INTERIM_AMOUNT, 0) END), 0) AS Interim_Compensation_Pending_Amount,
        COUNT(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('CO', 'C') THEN 1 END) AS Final_Compensation_No_of_cases_Disbursed,
        COALESCE(SUM(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('CO', 'C') THEN COALESCE(J.FINAL_AMOUNT, 0) END), 0) AS Final_Compensation_Disbursed_Amount,
        COUNT(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C', 'CO') THEN 1 END) AS Final_Compensation_No_of_cases_awarded,
        COALESCE(SUM(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C', 'CO') THEN COALESCE(J.FINAL_AMOUNT, 0) END), 0) AS Final_Compensation_awarded_Amount,
        COUNT(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN 1 END) AS Final_Compensation_No_of_cases_Pending,
        COALESCE(SUM(CASE WHEN J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') THEN COALESCE(J.FINAL_AMOUNT, 0) END), 0) AS Final_Compensation_Pending_Amount,
        COUNT(CASE WHEN O.CHARGE_SHEET_YES_NO = 'Yes' THEN 1 END) AS No_of_Chargesheet_filed,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND J.INTERIM_AWARDED_YES = 'Yes' AND S.PROCEED_INTERIM IS NOT NULL THEN 1 END) AS Disbursed1,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND J.INTERIM_AWARDED_YES = 'No' AND S.PROCEED_INTERIM IS NOT NULL THEN 1 END) AS Pending1,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' OR J.INTERIM_AWARDED_YES = 'Yes' AND S.PROCEED_INTERIM IS NOT NULL THEN 1 END) AS Not_applicable,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NOT NULL THEN 1 END) AS Disbursed2,
        COUNT(CASE WHEN O.WILLINGNESS_COMPENSATION = 'Yes' AND S.PROCEED_FINAL IS NULL THEN 1 END) AS Pending2,
        COUNT(CASE WHEN J.FINAL IS NOT NULL THEN 1 END) AS Disposed3,
        COUNT(CASE WHEN J.FINAL IS NULL THEN 1 END) AS Pending3
    FROM 
        TNEGA_OVERALL_T_DUP O
    JOIN 
        TNEGA_JUDGE_LOGIN_T J ON O.ID = J.COURT_ID
    JOIN 
        TNEA_SUPERINTENDENT_T S ON O.ID = S.COURT_ID
    GROUP BY 
        O.DISTRICT_NAME
    ORDER BY 
        1 ASC
) A;
    ";

    // Prepare the SQL statement
    $stmt = $read_db->prepare($sql);

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
            "message" => "No records found for the provided filters."
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
    //error_log($e->getMessage(), 3, '/var/log/api_errors.log');
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);

?>
