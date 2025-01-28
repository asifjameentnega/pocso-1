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
    if (!$read_db) {
        throw new Exception("Database connection not established.");
    }

    // Define the SQL query
    $sql = " SELECT DISTINCT 
        S.DISTRICT_NAME,
        (SELECT COALESCE(SUM(CASE WHEN J.JUDGEMENT = 'Acquittal' THEN 1 ELSE 0 END), 0) 
         FROM TNEGA_OVERALL_T_dup OT, TNEGA_JUDGE_LOGIN_T J 
         WHERE OT.ID = J.COURT_ID AND OT.DISTRICT_NAME = S.DISTRICT_NAME) AS Acquitted,
        (SELECT COALESCE(SUM(CASE WHEN J.JUDGEMENT = 'Convicted' THEN 1 ELSE 0 END), 0) 
         FROM TNEGA_OVERALL_T_dup OT, TNEGA_JUDGE_LOGIN_T J 
         WHERE OT.ID = J.COURT_ID AND OT.DISTRICT_NAME = S.DISTRICT_NAME) AS Convicted,
        (SELECT COALESCE(SUM(CASE WHEN J.JUDGEMENT = 'Others' THEN 1 ELSE 0 END), 0) 
         FROM TNEGA_OVERALL_T_dup OT, TNEGA_JUDGE_LOGIN_T J 
         WHERE OT.ID = J.COURT_ID AND OT.DISTRICT_NAME = S.DISTRICT_NAME) AS Others,
        (SELECT COALESCE(SUM(CASE WHEN J.JUDGEMENT IN ('Acquittal', 'Convicted', 'Others') THEN 1 ELSE 0 END), 0) 
         FROM TNEGA_OVERALL_T_dup OT, TNEGA_JUDGE_LOGIN_T J 
         WHERE OT.ID = J.COURT_ID AND OT.DISTRICT_NAME = S.DISTRICT_NAME) AS Total
    FROM 
        SIGNUP_T S
    WHERE 
        S.DISTRICT_NAME IS NOT NULL
        AND S.DISTRICT_NAME NOT IN ('State', 'District', 'Directorate')
    ORDER BY 
        S.DISTRICT_NAME ASC;
    ";
    

    // Prepare and execute the query
    $stmt = $read_db->prepare($sql);
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
            "message" => "No records found.",
          
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

?>
