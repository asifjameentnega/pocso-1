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

   

    // Define the full SQL query with placeholders for the dynamic conditions
    $sql = "SELECT 
    TO_CHAR(OT.DATE_OF_FIR, 'YYYY') AS \"Year\",
    SUM(CASE WHEN REQUEST_STATUS IN ('E','S','F1') THEN 1 ELSE 0 END) AS \"Registered\",
    SUM(CASE WHEN REQUEST_STATUS IN ('C','CO') THEN 1 ELSE 0 END) AS \"Disposed\"
FROM 
    TNEGA_OVERALL_T_dup OT
GROUP BY 
    TO_CHAR(OT.DATE_OF_FIR, 'YYYY')
ORDER BY 
    TO_CHAR(OT.DATE_OF_FIR, 'YYYY')";


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
            "message" => "No records found."
        ];
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => $e->getMessage()
    ];

}

// Output the response
header('Content-Type: application/json');
echo json_encode($response);

?>
