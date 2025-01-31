<?php

// Load dependencies
require_once('../../../../../../helper/header.php');
require_once('../../../../../../config/write_database.php');

// Define constants
define('DATE_FORMAT', 'Y-m-d H:i:s.u');

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => 0, "message" => "Method Not Allowed"]);
    exit;
}

// Retrieve input data
$inputData = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$requiredFields = ['COURT_ID', 'IFSC_CODE', 'BANK_NAME', 'BRANCH_NAME', 'ACCOUNT_NUMBER', 'ACCOUNT_HOLDER_NAME'];
foreach ($requiredFields as $field) {
    if (empty($inputData[$field])) {
        http_response_code(400);
        echo json_encode(["success" => 0, "message" => "Missing required field: $field"]);
        exit;
    }
}

// Sanitize and bind input
$courtId = trim($inputData['COURT_ID']);
$id = trim($inputData['COURT_ID'] ?? '');
$ifscCode = trim($inputData['IFSC_CODE']);
$bankName = trim($inputData['BANK_NAME']);
$branchName = trim($inputData['BRANCH_NAME']);
$accountNumber = trim($inputData['ACCOUNT_NUMBER']);
$accountHolder = trim($inputData['ACCOUNT_HOLDER_NAME']);
$orderNo = trim($inputData['ORDER_NO'] ?? '');
$courtFileDate = trim($inputData['COURT_FILE_DATE'] ?? '');
$nameOfCourt = trim($inputData['NAME_OF_COURT'] ?? '');
$type = trim($inputData['TYPE'] ?? '');
$interimOrderDate = trim($inputData['INTERIM_ORDER_DATE'] ?? '');
$interimAmount = trim($inputData['INTERIM_AMOUNT'] ?? '');
$finalOrderDate = trim($inputData['FINAL_ORDER_DATE'] ?? '');
$finalAmount = trim($inputData['FINAL_AMOUNT'] ?? '');
$judgement = trim($inputData['JUDGEMENT'] ?? '');
$changeBankAccount = trim($inputData['CHANGE_BANK_ACCOUNT'] ?? '');

try {
    // Begin transaction
    $write_db->beginTransaction();

    // 1. Update TNEGA_OVERALL_T_DUP
    $stmt1 = $write_db->prepare("UPDATE TNEGA_OVERALL_T_DUP 
        SET IFSC_CODE = :ifscCode,
            BANK_NAME = :bankName,
            BRANCH_NAME = :branchName,
            ACCOUNT_NUMBER = :accountNumber,
            ACCOUNT_HOLDER_NAME = :accountHolder,
            request_status = 'S'
        WHERE id = :courtId");
    $stmt1->execute(compact('ifscCode', 'bankName', 'branchName', 'accountNumber', 'accountHolder', 'courtId'));

    // 2. Update request_status in TNEGA_OVERALL_T_DUP
    $stmt2 = $write_db->prepare("UPDATE TNEGA_OVERALL_T_DUP
        SET request_status = 'C'
        WHERE ID = :courtId
        AND ID IN (SELECT COURT_ID FROM TNEA_SUPERINTENDENT_T WHERE COURT_ID = :id AND NEW_STATUS = 'Interim Order Status')");
    $stmt2->execute(compact('courtId', 'id'));

    // 3. Update TNEGA_JUDGE_LOGIN_T
    $stmt3 = $write_db->prepare("UPDATE TNEGA_JUDGE_LOGIN_T 
        SET ORDER_NO = :orderNo,
            COURT_FILE_DATE = :courtFileDate,
            NAME_OF_COURT = :nameOfCourt,
            TYPE = :type,
            INTERIM_ORDER_DATE = :interimOrderDate,
            INTERIM_AMOUNT = :interimAmount,
            FINAL_ORDER_DATE = :finalOrderDate,
            FINAL_AMOUNT = :finalAmount,
            JUDGEMENT = :judgement,
            CHANGE_BANK_ACCOUNT = :changeBankAccount,
            ACCOUNT_NUMBER = :accountNumber,
            BANK_NAME = :bankName,
            BRANCH_NAME = :branchName,
            IFSC_CODE = :ifscCode
        WHERE ID = :courtId");
    $stmt3->execute(compact('orderNo', 'courtFileDate', 'nameOfCourt', 'type', 'interimOrderDate', 'interimAmount', 'finalOrderDate', 'finalAmount', 'judgement', 'changeBankAccount', 'accountNumber', 'bankName', 'branchName', 'ifscCode', 'courtId'));

    // Commit transaction
    $write_db->commit();

    // Success response
    http_response_code(200);
    echo json_encode(["success" => 1, "message" => "All updates were successfully executed."]);
} catch (Exception $e) {
    $write_db->rollBack();
    http_response_code(500);
    echo json_encode(["success" => 0, "message" => $e->getMessage()]);
}

?>
