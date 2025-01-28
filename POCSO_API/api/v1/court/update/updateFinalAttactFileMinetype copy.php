<?php
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

// Constants
define('DATE_FORMAT', 'Y-m-d H:i:s.u');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    die();
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    // Decode input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate JSON decoding
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input.");
    }

    // Extract the necessary input values

    $FINALAttachFilename = $inputData['FINAL_ATTACH_FILENAME'] ?? null;
    $FINALAttachMimeType = $inputData['FINAL_ATTACH_MIMETYPE'] ?? null;
    $courtId = $inputData['COURT_ID'] ?? null;

    // Check if FINAL is not null
    if ( $courtId !== null) {

        // Begin transaction to ensure atomic updates
        $write_db->beginTransaction();

        // Prepare and execute the update query
        $sql = "UPDATE TNEGA_JUDGE_LOGIN_T 
                SET FINAL = 'FINAL',
                    FINAL_ATTACH_FILENAME = :FINAL_ATTACH_FILENAME,
                    FINAL_ATTACH_MIMETYPE = :FINAL_ATTACH_MIMETYPE
                WHERE COURT_ID = :COURT_ID";

        $stmt = $write_db->prepare($sql);
        $stmt->execute([
            
            ':FINAL_ATTACH_FILENAME' => $FINALAttachFilename,
            ':FINAL_ATTACH_MIMETYPE' => $FINALAttachMimeType,
            ':COURT_ID' => $courtId
        ]);

        // Commit transaction
        $write_db->commit();

        // Success response
        $response = ["success" => 1, "message" => "FINAL details updated successfully"];
    } else {
        // Error response if FINAL or COURT_ID is missing
        $response = ["success" => 0, "message" => "FINAL or COURT_ID cannot be null"];
    }

} catch (Exception $e) {
    // Rollback transaction in case of error
    $write_db->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => "Internal server error.",
        "error" => $e->getMessage()
    ];
}

// Output response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
