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
    $chargeSheetYesNo = $inputData['CHARGE_SHEET_YES_NO'] ?? null;
    $chargeSheetDate = $inputData['CHARGE_SHEET_DATE'] ?? null;
    $dateOfMedicalExaminationVictim = $inputData['DATE_OF_MEDICAL_EXAMINATION_VICTIM'] ?? null;
    $id = $inputData['ID'] ?? null;

    // Validate required fields
    if ($id === null) {
        throw new Exception("ID is required and cannot be null.");
    }

    // Begin transaction to ensure atomic updates
    $write_db->beginTransaction();

    // Prepare and execute the update query
    $sql = "UPDATE TNEGA_OVERALL_T_DUP
            SET CHARGE_SHEET_YES_NO = :CHARGE_SHEET_YES_NO,
                CHARGE_SHEET_DATE = :CHARGE_SHEET_DATE,
                DATE_OF_MEDICAL_EXAMINATION_VICTIM = :DATE_OF_MEDICAL_EXAMINATION_VICTIM
            WHERE ID = :ID";

    $stmt = $write_db->prepare($sql);
    $stmt->execute([
        ':CHARGE_SHEET_YES_NO' => $chargeSheetYesNo,
        ':CHARGE_SHEET_DATE' => $chargeSheetDate,
        ':DATE_OF_MEDICAL_EXAMINATION_VICTIM' => $dateOfMedicalExaminationVictim,
        ':ID' => $id
    ]);

    // Commit transaction
    $write_db->commit();

    // Check if the update affected rows
    if ($stmt->rowCount() > 0) {
        $response = ["success" => 1, "message" => "Record updated successfully"];
    } else {
        $response = ["success" => 0, "message" => "No matching record found to update"];
    }
} catch (Exception $e) {
    // Rollback transaction in case of error
    $write_db->rollBack();
    error_log($e->getMessage());
    http_response_code(500);
    $response = [
        "success" => 0,
        "message" => "Internal server error.",
        "error" => $e->getMessage() // For debugging, remove in production
    ];
}

// Output response as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
