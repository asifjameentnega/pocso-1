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
    $id = htmlspecialchars(strip_tags($inputData['id']));

    // Define the full SQL query
    $sql = "WITH overall_counts AS (
    SELECT 
        DISTRICT_NAME,
        POL_STAT,
        COUNT(*) AS No_of_POCSO_cases_filed,
        COUNT(*) FILTER (WHERE REQUEST_STATUS IN ('E', 'S', 'F1')) AS Case_Status_Pending,
        COUNT(*) FILTER (WHERE REQUEST_STATUS IN ('CO', 'C')) AS Case_Status_Completed,
        COUNT(*) FILTER (WHERE CHARGE_SHEET_YES_NO = 'Yes') AS No_of_Chargesheet_filed,
        COUNT(*) FILTER (WHERE WILLINGNESS_COMPENSATION = 'Yes') AS WILLINGNESS_COMPENSATION
    FROM TNEGA_OVERALL_T_DUP
    GROUP BY DISTRICT_NAME, POL_STAT
),
judgement_counts AS (
    SELECT 
        O.DISTRICT_NAME,
        O.POL_STAT,
        COUNT(*) FILTER (WHERE TRIM(J.JUDGEMENT) = 'Acquittal') AS No_of_cases_Acquitted,
        COUNT(*) FILTER (WHERE TRIM(J.JUDGEMENT) = 'Convicted') AS No_of_cases_Convicted,
        COUNT(*) FILTER (WHERE TRIM(J.JUDGEMENT) = 'Others') AS Other_Disposals
    FROM TNEGA_JUDGE_LOGIN_T J
    JOIN TNEGA_OVERALL_T_DUP O ON O.ID = J.COURT_ID
    GROUP BY O.DISTRICT_NAME, O.POL_STAT
),
compensation_counts AS (
    SELECT 
        O.DISTRICT_NAME,
        O.POL_STAT,
        COUNT(*) FILTER (WHERE J.TYPE = 'Interim Compensation') AS Interim_Compensation_No_of_cases_awarded,
        SUM(J.INTERIM_AMOUNT) FILTER (WHERE J.TYPE = 'Interim Compensation') AS Interim_Compensation_awarded_Amount,
        COUNT(*) FILTER (WHERE J.TYPE = 'Final Compensation') AS Final_Compensation_No_of_cases_awarded,
        SUM(J.FINAL_AMOUNT) FILTER (WHERE J.TYPE = 'Final Compensation') AS Final_Compensation_awarded_Amount,
		COUNT(*)FILTER (WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') AND O.ID = j.court_id AND DISTRICT_NAME = O.DISTRICT_NAME AND O.POL_STAT = O.POL_STAT ) AS Final_Compensation_No_of_cases_Pending,
        COUNT(*) FILTER (WHERE J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1')) AS Interim_Compensation_No_of_cases_Pending,
        SUM(J.INTERIM_AMOUNT) FILTER (WHERE J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1')) AS Interim_Compensation_Pending_Amount,
		SUM(j.INTERIM_AMOUNT) FILTER (WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = O.DISTRICT_NAME AND O.POL_STAT = O.POL_STAT) AS Interim_Compensation_Disbursed_Amount,
		SUM(J.FINAL_AMOUNT) FILTER (WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1')) AS Final_Compensation_Pending_Amount,
        COUNT(*) FILTER (WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = O.DISTRICT_NAME AND O.POL_STAT = O.POL_STAT)  AS Interim_Compensation_No_of_cases_Disbursed,
        COUNT(*) FILTER (WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C', 'CO')) AS Final_Compensation_No_of_cases_Disbursed,
        SUM(J.FINAL_AMOUNT) FILTER (WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C', 'CO')) AS Final_Compensation_Disbursed_Amount
    FROM TNEGA_JUDGE_LOGIN_T J
    JOIN TNEGA_OVERALL_T_DUP O ON O.ID = J.COURT_ID
    GROUP BY O.DISTRICT_NAME, O.POL_STAT
)

SELECT 
    OC.DISTRICT_NAME,
    OC.POL_STAT,
    OC.WILLINGNESS_COMPENSATION,
    OC.No_of_POCSO_cases_filed,
    OC.Case_Status_Pending,
    OC.Case_Status_Completed,
    JC.No_of_cases_Acquitted,
    JC.No_of_cases_Convicted,
    JC.Other_Disposals,
    CC.Interim_Compensation_No_of_cases_awarded,
    CC.Interim_Compensation_awarded_Amount,
	CC.Interim_Compensation_No_of_cases_Disbursed,
    CC.Interim_Compensation_Disbursed_Amount,
	CC.Interim_Compensation_No_of_cases_Pending,
    CC.Interim_Compensation_Pending_Amount,
	 CC.Final_Compensation_No_of_cases_Disbursed,
	  CC.Final_Compensation_Disbursed_Amount,
	  CC.Final_Compensation_No_of_cases_awarded,
    CC.Final_Compensation_awarded_Amount,
	CC.Final_Compensation_No_of_cases_Pending,
	CC.Final_Compensation_Pending_Amount,
	OC.No_of_Chargesheet_filed,
    (JC.No_of_cases_Convicted / NULLIF(CC.Final_Compensation_No_of_cases_awarded, 0) * 100) AS Conviction_rate,
    (CC.Interim_Compensation_Pending_Amount + CC.Final_Compensation_Pending_Amount) AS TOTAL
FROM overall_counts OC LEFT JOIN judgement_counts JC 
                      ON OC.DISTRICT_NAME = JC.DISTRICT_NAME 
  AND OC.POL_STAT = JC.POL_STAT  LEFT JOIN compensation_counts CC 
                      ON OC.DISTRICT_NAME = CC.DISTRICT_NAME 
  AND OC.POL_STAT = CC.POL_STAT JOIN SIGNUP_T S 
  ON OC.DISTRICT_NAME = S.NAME
  AND S.ID = :APP_EMPLOYEE_ID
  ORDER BY 1 ASC ;
    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':APP_EMPLOYEE_ID', $id);

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
            "message" => "No records found for the provided id."
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
 //   error_log($e->getMessage(), 3, '/var/log/api_errors.log');
}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);
