<?php

// Load dependencies
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

// Define constants
define('DATE_FORMAT', 'Y-m-d H:i:s.u');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    die();
}

// Initialize response
$response = ["success" => 0, "message" => "An error occurred"];

try {
    // Retrieve and validate input
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (empty($inputData['COURT_ID_1'])) {
        throw new Exception("Invalid request. Missing required parameter: COURT_ID_1.");
    }

    // Bind and sanitize input
    $courtId = $inputData['COURT_ID_1'];
    $newStatus = $inputData['NEW_STATUS'] ?? null;
    $interimPaymentStatus = $inputData['INTERIM_PAYMENT_STATUS'] ?? null;
    $finalPaymentStatus = $inputData['FINAL_PAYMENT_STATUS'] ?? null;
    $interimProceedings = $inputData['INTERIM_PROCEEDINGS'] ?? null;
    $proceedInterim = $inputData['PROCEED_INTERIM'] ?? null;
    $proceedFilename = $inputData['FILENAME'] ?? null;
    $proceedMimeType = $inputData['MIME_TYPE'] ?? null;
    $finalProceedings = $inputData['FINAL_PROCEEDINGS'] ?? null;
    $proceedFinal = $inputData['PROCEED_FINAL'] ?? null;

    // Begin transaction
    $write_db->beginTransaction();

    // 1. Update request status to 'F1'
        $stmt1 = $write_db->prepare("UPDATE TNEGA_OVERALL_T_DUP 
        SET request_status = 'F2'
        WHERE ID = :COURT_ID_1
        AND ID = (SELECT J.COURT_ID FROM TNEGA_JUDGE_LOGIN_T J WHERE J.COURT_ID =:COURT_ID_1 AND J.TYPE = 'Interim Compensation')");

        $stmt1->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
        $stmt1->execute();

 

    // 2. Update request status to 'CO' for final compensation
    $stmt2 = $write_db->prepare("
        UPDATE TNEGA_OVERALL_T_DUP 
        SET request_status = 'CO'
        WHERE ID = :COURT_ID_1
        AND ID = (SELECT J.COURT_ID FROM TNEGA_JUDGE_LOGIN_T J WHERE J.COURT_ID = :COURT_ID_1 AND J.TYPE = 'Final Compensation')
    ");
    $stmt2->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
    $stmt2->execute();

    // 3. Update status and payment statuses in TNEA_SUPERINTENDENT_T
    $stmt3 = $write_db->prepare("
        UPDATE TNEA_SUPERINTENDENT_T
        SET NEW_STATUS = :NEW_STATUS,
            INTERIM_PAYMENT_STATUS = :INTERIM_PAYMENT_STATUS,
            FINAL_PAYMENT_STATUS = :FINAL_PAYMENT_STATUS
        WHERE court_ID = :COURT_ID_1
    ");
    $stmt3->bindParam(':NEW_STATUS', $newStatus, PDO::PARAM_STR);
    $stmt3->bindParam(':INTERIM_PAYMENT_STATUS', $interimPaymentStatus, PDO::PARAM_STR);
    $stmt3->bindParam(':FINAL_PAYMENT_STATUS', $finalPaymentStatus, PDO::PARAM_STR);
    $stmt3->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
    $stmt3->execute();
  
    // 4. Update request status to 'CO' if FINAL_AMOUNT is not null
    $stmt4 = $write_db->prepare("
        UPDATE TNEGA_OVERALL_T_DUP
        SET request_status = 'CO'
        WHERE ID = :COURT_ID_1
        AND EXISTS (
            SELECT 1
            FROM TNEGA_JUDGE_LOGIN_T
            WHERE COURT_ID = :COURT_ID_1
            AND FINAL_AMOUNT IS NOT NULL
        )
    ");
    $stmt4->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
    $stmt4->execute();

    // 5. Update INTERIM_PROCEEDINGS if provided
    if ($interimProceedings !== null) {
        $stmt5 = $write_db->prepare('
            UPDATE TNEA_SUPERINTENDENT_T
            SET PROCEED_INTERIM = :PROCEED_INTERIM,
                PROCEED_ATTACH_FILENAME = :FILENAME,
                PROCEED_ATTACH_MIMETYPE = :MIME_TYPE
            WHERE COURT_ID = :COURT_ID_1
        ');
        $stmt5->bindParam(':PROCEED_INTERIM', $proceedInterim, PDO::PARAM_STR);
        $stmt5->bindParam(':FILENAME', $proceedFilename, PDO::PARAM_STR);
        $stmt5->bindParam(':MIME_TYPE', $proceedMimeType, PDO::PARAM_STR);
        $stmt5->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
        $stmt5->execute();
    }
    
    // 6. Update FINAL_PROCEEDINGS if provided
    if ($finalProceedings !== null) {
        $stmt6 = $write_db->prepare('
            UPDATE TNEA_SUPERINTENDENT_T
            SET PROCEED_FINAL = :PROCEED_FINAL,
                NEFT_ATTACH_FILENAME = :FILENAME,
                NEFT_ATTACH_MIMETYPE = :MIME_TYPE
            WHERE COURT_ID = :COURT_ID_1
        ');
        $stmt6->bindParam(':PROCEED_FINAL', $proceedFinal, PDO::PARAM_STR);
        $stmt6->bindParam(':FILENAME', $proceedFilename, PDO::PARAM_STR);
        $stmt6->bindParam(':MIME_TYPE', $proceedMimeType, PDO::PARAM_STR);
        $stmt6->bindParam(':COURT_ID_1', $courtId, PDO::PARAM_INT);
        $stmt6->execute();
    }
    
    // Commit transaction
    $write_db->commit();

    // Success response
    http_response_code(200);
    $response = ["success" => 1, "message" => "All updates were successfully executed."];
} catch (Exception $e) {
    // Rollback transaction in case of error
    $write_db->rollBack();

    http_response_code(500); // Internal Server Error
    $response = ["success" => 0, "message" => $e->getMessage()];
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);
