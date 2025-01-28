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
    if (!isset($inputData['DISTRICT_NAME']) || empty($inputData['DISTRICT_NAME'])) {
        throw new Exception("Invalid input. 'DISTRICT_NAME' is required.");
    }

    // Sanitize input
    $district_name = htmlspecialchars(strip_tags($inputData['DISTRICT_NAME']));
    
    // Optionally sanitize and retrieve other filtering parameters
    $pol_stat = isset($inputData['POL_STAT']) ? $inputData['POL_STAT'] : null;

    // Define the full SQL query with placeholders for dynamic conditions
    $sql = "
    SELECT 
        ROW_NUMBER() OVER (ORDER BY A.DISTRICT_NAME) AS ROWNUM, 
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
            (SELECT COUNT(*) 
             FROM TNEGA_OVERALL_T_DUP O 
             WHERE O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT AND WILLINGNESS_COMPENSATION = 'Yes') AS WILLINGNESS_COMPENSATION,
            (SELECT COUNT(*) 
             FROM TNEGA_OVERALL_T_DUP O 
             WHERE O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"No_of_POCSO_cases_filed\",
            (SELECT COUNT(*) 
             FROM TNEGA_OVERALL_T_DUP O 
             WHERE O.REQUEST_STATUS IN ('E', 'S', 'F1') AND O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Case_Status_Pending\",
            (SELECT COUNT(*) 
             FROM TNEGA_OVERALL_T_DUP O 
             WHERE O.REQUEST_STATUS IN ('CO', 'C') AND O.DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Case_Status_Completed\",
            COALESCE((SELECT COUNT(*) 
                      FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O 
                      WHERE J.JUDGEMENT = 'Acquittal' AND O.ID = J.COURT_ID AND O.POL_STAT = OT.POL_STAT AND DISTRICT_NAME = OT.DISTRICT_NAME 
                      GROUP BY DISTRICT_NAME), 0) AS \"No_of_cases_Acquitted\",
            COALESCE((SELECT COUNT(*) 
                      FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O 
                      WHERE TRIM(J.JUDGEMENT) = 'Convicted' AND O.ID = J.COURT_ID AND DISTRICT_NAME = O.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT), 0) AS \"No_of_cases_Convicted\",
            (SELECT COUNT(*) 
             FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O 
             WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('C') AND O.ID = J.COURT_ID 
             AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Final_Compensation_No_of_cases_awarded\",
            (SELECT COALESCE(SUM(J.FINAL_AMOUNT), 0) 
             FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O 
             WHERE J.TYPE = 'Final Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') AND O.ID = J.COURT_ID 
             AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Final_Compensation_Pending_Amount\",
            (SELECT COALESCE(SUM(J.INTERIM_AMOUNT), 0) 
             FROM TNEGA_JUDGE_LOGIN_T J, TNEGA_OVERALL_T_DUP O 
             WHERE J.TYPE = 'Interim Compensation' AND O.REQUEST_STATUS IN ('E', 'S', 'F1', 'C') AND O.ID = J.COURT_ID 
             AND DISTRICT_NAME = OT.DISTRICT_NAME AND O.POL_STAT = OT.POL_STAT) AS \"Interim_Compensation_Pending_Amount\"
        FROM TNEGA_OVERALL_T_DUP OT
        WHERE OT.DISTRICT_NAME = :district_name
        ";

// Append the additional condition if $pol_stat is provided
if ($pol_stat) {
    $sql .= " AND OT.POL_STAT = :pol_stat";
}

$sql .= "
    ) A
";

    // Prepare SQL query
    $stmt = $read_db->prepare($sql);
    $stmt->bindParam(":district_name", $district_name, PDO::PARAM_STR);

    // Bind pol_stat if present
    if ($pol_stat) {
        $stmt->bindParam(":pol_stat", $pol_stat, PDO::PARAM_STR);
    }

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
