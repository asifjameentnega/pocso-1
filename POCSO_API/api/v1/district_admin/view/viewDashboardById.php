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
    $sql = "
        SELECT 
            ROW_NUMBER() OVER (ORDER BY 1 ASC) AS ROWNUM,
            A.*, 
            ROUND(
                COALESCE(
                    (A.\"No_of_cases_Convicted\" / NULLIF(A.\"Final_Compensation_No_of_cases_awarded\", 0) * 100),
                    0
                ), 
                2
            ) AS \"Conviction_rate\",
            (COALESCE(A.\"Interim_Compensation_Pending_Amount\", 0) + COALESCE(A.\"Final_Compensation_Pending_Amount\", 0)) AS TOTAL
        FROM (
            SELECT DISTINCT
                OT.DISTRICT_NAME,
                OT.POL_STAT,
                (SELECT COUNT(*) FROM TNEGA_OVERALL_T_DUP O WHERE O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT AND WILLINGNESS_COMPENSATION = 'Yes' ) AS WILLINGNESS_COMPENSATION,
                (SELECT COUNT(*) FROM TNEGA_OVERALL_T_DUP O WHERE O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"No_of_POCSO_cases_filed\",
                (SELECT COUNT(*) FROM TNEGA_OVERALL_T_DUP O WHERE O.REQUEST_STATUS IN ('E', 'S', 'F1') AND O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Case_Status_Pending\",
                (SELECT COUNT(*) FROM TNEGA_OVERALL_T_DUP O WHERE O.REQUEST_STATUS IN ('CO', 'C') AND O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Case_Status_Completed\",
                COALESCE((SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.JUDGEMENT = 'Acquittal' AND O.ID = J.COURT_ID AND O.POL_STAT = OT.POL_STAT AND DISTRICT_NAME = OT.DISTRICT_NAME GROUP BY DISTRICT_NAME), 0) AS \"No_of_cases_Acquitted\",
                COALESCE((SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE TRIM(J.JUDGEMENT) = 'Convicted' AND O.ID = J.COURT_ID AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT), 0) AS \"No_of_cases_Convicted\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE TRIM(J.JUDGEMENT) = 'Others' AND O.ID = J.COURT_ID AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Other_Disposals\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Interim_Compensation_No_of_cases_awarded\",
                (SELECT COALESCE(SUM(j.INTERIM_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Interim_Compensation_awarded_Amount\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Interim_Compensation_No_of_cases_Disbursed\",
                (SELECT COALESCE(SUM(j.INTERIM_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE j.INTERIM_AMOUNT IS NOT NULL AND o.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Interim_Compensation_Disbursed_Amount\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Interim_Compensation_No_of_cases_Pending\",
                (SELECT COALESCE(SUM(j.INTERIM_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Interim_Compensation_Pending_Amount\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('CO','C') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Final_Compensation_No_of_cases_Disbursed\",
                (SELECT COALESCE(SUM(j.FINAL_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('CO','C') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Final_Compensation_Disbursed_Amount\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C','CO') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Final_Compensation_No_of_cases_awarded\",
                (SELECT COALESCE(SUM(j.FINAL_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C','CO') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Final_Compensation_awarded_Amount\",
                (SELECT COUNT(*) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT ) AS \"Final_Compensation_No_of_cases_Pending\",
                (SELECT COALESCE(SUM(j.FINAL_AMOUNT), 0) FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1') AND O.ID = j.court_id AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Final_Compensation_Pending_Amount\",
                (SELECT COUNT(*) FROM TNEGA_OVERALL_T_DUP WHERE CHARGE_SHEET_YES_NO IN ('Yes') AND DISTRICT_NAME = OT.DISTRICT_NAME AND POL_STAT = OT.POL_STAT) AS \"No_of_Chargesheet_filed\"
            FROM 
                TNEGA_OVERALL_T_DUP OT
            ORDER BY 1 ASC
        ) A
        INNER JOIN SIGNUP_T S ON A.DISTRICT_NAME = S.NAME
        WHERE 
            S.ID = :id;
    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':id', $id);

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
