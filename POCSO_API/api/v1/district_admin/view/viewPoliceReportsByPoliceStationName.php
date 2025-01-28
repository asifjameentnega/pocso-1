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
    if (!isset($inputData['policeStaionName']) || empty($inputData['policeStaionName'])) {
        throw new Exception("Invalid input. 'policeStaionName' is required.");
    }

    // Sanitize input
    $policeStationName = htmlspecialchars(strip_tags($inputData['policeStaionName']));

    // Define the full SQL query
    $sql = "SELECT 
    OT.ID,
    OT.POL_STAT,
    OT.FIR_NO,
    OT.DATE_OF_FIR,
    (CASE 
        WHEN TJLT.COURT_FILE_DATE IS NOT NULL THEN 'Yes'
        ELSE 'No'
    END) AS \"Chargesheet filled Status\",
    TJLT.ORDER_NO,
    TJLT.COURT_FILE_DATE,
    CASE REQUEST_STATUS
        WHEN 'E' THEN 'Pending'
        WHEN 'S' THEN 'Pending'
        WHEN 'F1' THEN 'Pending'
        WHEN 'C' THEN 'Pending'
        WHEN 'CO' THEN 'Disposed'
    END AS STATUS,
    TJLT.JUDGEMENT,
    TJLT.INTERIM_AMOUNT,
    CASE REQUEST_STATUS
        WHEN 'E' THEN 'Pending'
        WHEN 'S' THEN 'Pending'
        WHEN 'F1' THEN 'Pending'
        WHEN 'C' THEN 'Pending'
        WHEN 'CO' THEN 'Completed'
    END AS INTERIM_STATUS,
    TJLT.FINAL_AMOUNT,
    CASE REQUEST_STATUS
        WHEN 'E' THEN 'Pending'
        WHEN 'S' THEN 'Pending'
        WHEN 'F1' THEN 'Pending'
        WHEN 'C' THEN 'Pending'
        WHEN 'CO' THEN 'Completed'
    END AS FINAL_STATUS,
    CURRENT_DATE - OT.DATE_OF_FIR AS \"Aging_of_cases\",
    OT.CHARGE_SHEET_DATE - OT.DATE_OF_FIR AS \"Aging_of_chargesheet_date\"
FROM 
    TNEGA_OVERALL_T_DUP OT
INNER JOIN 
    TNEGA_JUDGE_LOGIN_T TJLT 
    ON TJLT.COURT_ID = OT.ID 
WHERE 
    OT.POL_STAT = :P83_POLICE;


    ";

    // Prepare the statement
    $stmt = $read_db->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':P83_POLICE', $policeStationName);

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
            "message" => "No records found for the provided Police Station Name."
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
