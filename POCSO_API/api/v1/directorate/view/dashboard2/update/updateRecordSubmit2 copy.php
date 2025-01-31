<?php

require_once('../../../../../../helper/header.php');
require_once('../../../../../../config/write_database.php');
// Define constants
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
    // Retrieve and validate input
    $inputData = json_decode(file_get_contents("php://input"), true);
    
    if (empty($inputData['COURT_ID'])) {
        throw new Exception("Invalid request. Missing required parameter: COURT_ID.");
    }
    
    // Bind and sanitize input
    $courtId = $inputData['COURT_ID'];
    $courtId1 = $inputData['COURT_ID_1'] ?? null;
    $id = $inputData['ID'] ?? null;
    
    $ifscCode = $inputData['IFSC_CODE'] ?? null;
    $ifscCode1 = $inputData['IFSC_CODE_1'] ?? null;
    $bankName = $inputData['BANK_NAME'] ?? null;
    $bankName1 = $inputData['BANK_NAME_1'] ?? null;
    $branchName = $inputData['BRANCH_NAME'] ?? null;
    $branchName1 = $inputData['BRANCH_NAME_1'] ?? null;
    $accountNumber = $inputData['ACCOUNT_NUMBER'] ?? null;
    $accountNumber1 = $inputData['ACCOUNT_NUMBER_1'] ?? null;
    $accountHolder = $inputData['ACCOUNT_HOLDER_NAME'] ?? null;
    $bakAccount = $inputData['BAK_ACCOUNT'] ?? null;
    $orderNo = $inputData['ORDER_NO'] ?? null;
    $courtFileDate = $inputData['COURT_FILE_DATE'] ?? null;
    $nameOfCourt = $inputData['NAME_OF_COURT'] ?? null;
    $type = $inputData['TYPE'] ?? null;
    $interimOrderDate = $inputData['INTERIM_ORDER_DATE'] ?? null;
    $interimAmount = $inputData['INTERIM_AMOUNT'] ?? null;
    $interim = $inputData['INTERIM'] ?? null;
    $interimAttachFilename = $inputData['INTERIM_ATTACH_FILENAME'] ?? null;
    $interimAttachMimeType = $inputData['INTERIM_ATTACH_MIMETYPE'] ?? null;
    $finalOrderDate = $inputData['FINAL_ORDER_DATE'] ?? null;
    $finalAmount = $inputData['FINAL_AMOUNT'] ?? null;
    $final = $inputData['FINAL'] ?? null;
    $finalAttachFilename = $inputData['FINAL_ATTACH_FILENAME'] ?? null;
    $finalAttachMimeType = $inputData['FINAL_ATTACH_MIMETYPE'] ?? null;
    $judgement = $inputData['JUDGEMENT'] ?? null;
    $interimAwardedYes = $inputData['INTERIM_AWARDED_YES'] ?? null;
    $changeBankAccount = $inputData['CHANGE_BANK_ACCOUNT'] ?? null;
    
    // Begin transaction
    $write_db->beginTransaction();

    // 1. Update TNEGA_OVERALL_T_DUP
    $stmt1 = $write_db->prepare("UPDATE TNEGA_OVERALL_T_DUP 
        SET IFSC_CODE = COALESCE(:ifscCode1, :ifscCode),
            BANK_NAME = COALESCE(:bankName1, :bankName),
            BRANCH_NAME = COALESCE(:branchName1, :branchName),
            ACCOUNT_NUMBER = COALESCE(:accountNumber1, :accountNumber),
            ACCOUNT_HOLDER_NAME = COALESCE(:bakAccount, :accountHolder),
            request_status = 'S'
        WHERE id = :courtId");
    $stmt1->execute(compact('ifscCode', 'ifscCode1', 'bankName', 'bankName1', 'branchName', 'branchName1', 'accountNumber', 'accountNumber1', 'bakAccount', 'accountHolder', 'courtId'));

    // 2. Update request_status in TNEGA_OVERALL_T_DUP
    $stmt2 = $write_db->prepare("UPDATE TNEGA_OVERALL_T_DUP O
        SET O.request_status = 'C'
        WHERE O.ID = :courtId1
        AND O.ID = (SELECT J.COURT_ID FROM TNEA_SUPERINTENDENT_T J WHERE J.COURT_ID = :id AND J.NEW_STATUS = 'Interim Order Status')");
    $stmt2->execute(compact('courtId1', 'id'));

    // 3. Update TNEGA_JUDGE_LOGIN_T
    $stmt3 = $write_db->prepare("UPDATE TNEGA_JUDGE_LOGIN_T 
        SET ORDER_NO = :orderNo,
            COURT_FILE_DATE = :courtFileDate,
            NAME_OF_COURT = :nameOfCourt,
            TYPE = :type,
            INTERIM_ORDER_DATE = :interimOrderDate,
            INTERIM_AMOUNT = :interimAmount,
            INTERIM = :interim,
            INTERIM_ATTACH_FILENAME = :interimAttachFilename,
            INTERIM_ATTACH_MIMETYPE = :interimAttachMimeType,
            FINAL_ORDER_DATE = :finalOrderDate,
            FINAL_AMOUNT = :finalAmount,
            FINAL = :final,
            FINAL_ATTACH_FILENAME = :finalAttachFilename,
            FINAL_ATTACH_MIMETYPE = :finalAttachMimeType,
            JUDGEMENT = :judgement,
            INTERIM_AWARDED_YES = :interimAwardedYes,
            CHANGE_BANK_ACCOUNT = :changeBankAccount,
            ACCOUNT_NUMBER = :accountNumber,
            BAK_ACCOUNT = :bakAccount,
            BANK_NAME = :bankName,
            BRANCH_NAME = :branchName,
            IFSC_CODE = :ifscCode
        WHERE ID = :courtId");
    $stmt3->execute(compact('orderNo', 'courtFileDate', 'nameOfCourt', 'type', 'interimOrderDate', 'interimAmount', 'interim', 'interimAttachFilename', 'interimAttachMimeType', 'finalOrderDate', 'finalAmount', 'final', 'finalAttachFilename', 'finalAttachMimeType', 'judgement', 'interimAwardedYes', 'changeBankAccount', 'accountNumber', 'bakAccount', 'bankName', 'branchName', 'ifscCode', 'courtId'));

    // Commit transaction
    $write_db->commit();

    // Success response
    http_response_code(200);
    $response = ["success" => 1, "message" => "All updates were successfully executed."];
} catch (Exception $e) {
    $write_db->rollBack();
    http_response_code(500);
    $response = ["success" => 0, "message" => $e->getMessage()];
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);