<?php

require_once('../../../../../../helper/header.php');
require_once('../../../../../../config/write_database.php');

define('DATE_FORMAT', 'Y-m-d H:i:s.u');

if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    die();
}

$response = ["success" => 0, "message" => "An error occurred"];

try {
    $inputData = json_decode(file_get_contents("php://input"), true);
    
    if (empty($inputData['ID']) || empty($inputData['COURT_ID'])) {
        throw new Exception("Invalid request. Missing required parameters.");
    }
    
    $id = $inputData['ID'];
    $courtId1 = $inputData['COURT_ID'];
    $newStatus = $inputData['NEW_STATUS'] ?? null;
    $interimPaymentStatus = $inputData['INTERIM_PAYMENT_STATUS'] ?? null;
    $proceedInterim = $inputData['PROCEED_INTERIM'] ?? null;
    $proceedAttachFilename = $inputData['PROCEED_ATTACH_FILENAME'] ?? null;
    $proceedAttachMimeType = $inputData['PROCEED_ATTACH_MIMETYPE'] ?? null;
    $finalPaymentStatus = $inputData['FINAL_PAYMENT_STATUS'] ?? null;
    $proceedFinal = $inputData['PROCEED_FINAL'] ?? null;
    $neftAttachFilename = $inputData['NEFT_ATTACH_FILENAME'] ?? null;
    $neftAttachMimeType = $inputData['NEFT_ATTACH_MIMETYPE'] ?? null;

    $write_db->beginTransaction();

    // 1. Update TNEA_SUPERINTENDENT_T
    $stmt1 = $write_db->prepare("UPDATE TNEA_SUPERINTENDENT_T 
        SET NEW_STATUS = :newStatus,
            INTERIM_PAYMENT_STATUS = :interimPaymentStatus,
            PROCEED_INTERIM = :proceedInterim,
            PROCEED_ATTACH_FILENAME = :proceedAttachFilename,
            PROCEED_ATTACH_MIMETYPE = :proceedAttachMimeType,
            FINAL_PAYMENT_STATUS = :finalPaymentStatus,
            PROCEED_FINAL = :proceedFinal,
            NEFT_ATTACH_FILENAME = :neftAttachFilename,
            NEFT_ATTACH_MIMETYPE = :neftAttachMimeType
        WHERE ID = :id");
    $stmt1->execute(compact('newStatus', 'interimPaymentStatus', 'proceedInterim', 'proceedAttachFilename', 'proceedAttachMimeType', 'finalPaymentStatus', 'proceedFinal', 'neftAttachFilename', 'neftAttachMimeType', 'id'));

    if ($stmt1->rowCount() === 0) {
        throw new Exception("No record found with ID: $id in TNEA_SUPERINTENDENT_T.");
    }

    
// 2. Update TNEGA_OVERALL_T_DUP for Interim Compensation
$stmt2 =$write_db->prepare("
UPDATE TNEGA_OVERALL_T_DUP 
SET request_status = 
    CASE 
        WHEN ID = :courtId1 
        AND ID IN (SELECT COURT_ID FROM TNEGA_JUDGE_LOGIN_T 
                   WHERE COURT_ID = :courtId1 
                   AND TYPE = 'Interim Compensation') 
        THEN 'F1'
        
        WHEN ID = :courtId1 
        AND ID IN (SELECT COURT_ID FROM TNEGA_JUDGE_LOGIN_T 
                   WHERE COURT_ID = :courtId1 
                   AND TYPE = 'Final Compensation' 
                   AND FINAL_AMOUNT IS NOT NULL) 
        THEN 'CO'
        
        ELSE request_status  -- Keeps existing value if no condition matches
    END
WHERE ID = :courtId1
");
$stmt2->execute(compact('courtId1'));
if ($stmt2->rowCount() === 0) {
    throw new Exception("No record found with courtId : $courtId1 in TNEGA_JUDGE_LOGIN_T.");
}


    $write_db->commit();
    
    http_response_code(200);
    $response = ["success" => 1, "message" => "All updates were successfully executed."];
} catch (Exception $e) {
    $write_db->rollBack();
    http_response_code(500);
    $response = ["success" => 0, "message" => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
