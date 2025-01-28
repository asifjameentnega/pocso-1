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
    if (!isset($inputData['POL_STAT']) || empty($inputData['POL_STAT'])) {
        throw new Exception("Invalid input. 'POL_STAT' is required.");
    }

    // Sanitize input
    $pol_stat = htmlspecialchars(strip_tags($inputData['POL_STAT']));

    // Define the full SQL query
    $sql = "
        SELECT 
            OT.ID,
            OT.POL_STAT,
            OT.FIR_NO,
            OT.DATE_OF_FIR,
            COALESCE(TO_CHAR(OT.CHARGE_SHEET_DATE, 'DD-MON-YYYY'), '-') AS CHARGE_SHEET_DATE,
            OT.COMPLAINT_MADE,
            OT.COMPLAINT_NAME,
            (CASE 
                WHEN TJLT.COURT_FILE_DATE IS NOT NULL THEN 'Yes'
                ELSE 'No'
            END) AS \"Chargesheet filled Status\",
            TJLT.ORDER_NO,
            TJLT.COURT_FILE_DATE,
            CASE OT.REQUEST_STATUS
                WHEN 'E' THEN 'Pending'
                WHEN 'S' THEN 'Pending'
                WHEN 'F1' THEN 'Pending'
                WHEN 'C' THEN 'Pending'
                WHEN 'CO' THEN 'Disbursed'
            END AS STATUS,
            TJLT.JUDGEMENT,
            TJLT.INTERIM_AMOUNT,
            CASE OT.REQUEST_STATUS
                WHEN 'E' THEN 'Pending'
                WHEN 'S' THEN 'Pending'
                WHEN 'F1' THEN 'Pending'
                WHEN 'C' THEN 'Pending'
                WHEN 'CO' THEN 'Disbursed'
            END AS INTERIM_STATUS,
            TJLT.FINAL_AMOUNT,
            CASE OT.REQUEST_STATUS
                WHEN 'E' THEN 'Pending'
                WHEN 'S' THEN 'Pending'
                WHEN 'F1' THEN 'Pending'
                WHEN 'C' THEN 'Pending'
                WHEN 'CO' THEN 'Disbursed'
            END AS FINAL_STATUS,
            CASE 
                WHEN OT.DATE_OF_FIR IS NOT NULL AND TJLT.final_order_date IS NOT NULL THEN 
                    DATE_PART('day', OT.DATE_OF_FIR - TJLT.final_order_date)
                ELSE 0
            END AS Aging_of_cases,
            CASE 
                WHEN OT.CHARGE_SHEET_DATE IS NOT NULL AND TJLT.final_order_date IS NOT NULL THEN 
                    DATE_PART('day', OT.CHARGE_SHEET_DATE - TJLT.final_order_date)
                ELSE 0
            END AS Aging_of_chargesheet_date
        FROM 
            TNEGA_OVERALL_T_DUP OT
        JOIN 
            TNEGA_JUDGE_LOGIN_T TJLT 
        ON 
            TJLT.COURT_ID = OT.ID
        WHERE 
            OT.POL_STAT = :P63_POLICE";

    // Prepare the SQL query
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":P63_POLICE", $pol_stat, PDO::PARAM_STR);

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
