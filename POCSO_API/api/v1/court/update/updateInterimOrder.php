<?php
require_once('../../../../helper/header.php');
require_once('../../../../helper/encryptDecrypt.php');
require_once('../../../../config/write_database.php');

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

    // Extract and validate required fields
    $requiredFields = [
        'COURT_ID', 'ORDER_NO', 'COURT_FILE_DATE', 'NAME_OF_COURT', 
        'INTERIM_ORDER_DATE', 'INTERIM_AMOUNT', 'JUDGEMENT', 'TYPE', 
        'CHANGE_BANK_ACCOUNT', 'INTERIM_AWARDED_YES', 'BRANCH_NAME_1', 
        'BAK_ACCOUNT', 'IFSC_CODE_1', 'ACCOUNT_NUMBER_1', 'BANK_NAME_1'
    ];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($inputData[$field])) {
            $missingFields[] = $field;
        }
    }

    // Throw an error if any required field is missing
    if (!empty($missingFields)) {
        throw new Exception("Invalid input. The following fields are required and cannot be null: " . implode(', ', $missingFields));
    }

    // Validate INTERIM_AMOUNT and INTERIM_ORDER_DATE
    if (empty($inputData['INTERIM_AMOUNT']) || empty($inputData['INTERIM_ORDER_DATE'])) {
        throw new Exception("INTERIM_AMOUNT and INTERIM_ORDER_DATE are required and cannot be null.");
    }

    // Assign validated input data to variables
    $COURT_ID = $inputData['COURT_ID'];
    $ORDER_NO = $inputData['ORDER_NO'];
    $COURT_FILE_DATE = $inputData['COURT_FILE_DATE'];
    $NAME_OF_COURT = $inputData['NAME_OF_COURT'];
    $INTERIM_ORDER_DATE = $inputData['INTERIM_ORDER_DATE'];
    $INTERIM_AMOUNT = $inputData['INTERIM_AMOUNT'];
    $JUDGEMENT = $inputData['JUDGEMENT'];
    $TYPE = $inputData['TYPE'];
    $CHANGE_BANK_ACCOUNT = $inputData['CHANGE_BANK_ACCOUNT'];
    $INTERIM_AWARDED_YES = $inputData['INTERIM_AWARDED_YES'];
    $BRANCH_NAME_1 = $inputData['BRANCH_NAME_1'];
    $BAK_ACCOUNT = $inputData['BAK_ACCOUNT'];
    $IFSC_CODE_1 = $inputData['IFSC_CODE_1'];
    $ACCOUNT_NUMBER_1 = $inputData['ACCOUNT_NUMBER_1'];
    $BANK_NAME_1 = $inputData['BANK_NAME_1'];

    // Prepare SQL query
    $sql = "UPDATE TNEGA_JUDGE_LOGIN_T
            SET ORDER_NO = :P53_ORDER_NO,
                COURT_FILE_DATE = :P53_COURT_FILE_DATE,
                NAME_OF_COURT = :P53_NAME_OF_COURT,
                INTERIM_ORDER_DATE = :P53_INTERIM_ORDER_DATE,
                INTERIM_AMOUNT = :P53_INTERIM_AMOUNT,
                JUDGEMENT = :P53_JUDGEMENT,
                TYPE = :P53_TYPE,
                CHANGE_BANK_ACCOUNT = :P53_CHANGE_BANK_ACCOUNT,
                INTERIM_AWARDED_YES = :P53_INTERIM_AWARDED_YES,
                BRANCH_NAME = :P53_BRANCH_NAME_1,
                BAK_ACCOUNT = :P53_BAK_ACCOUNT,
                IFSC_CODE = :P53_IFSC_CODE_1,
                ACCOUNT_NUMBER = :P53_ACCOUNT_NUMBER_1,
                BANK_NAME = :P53_BANK_NAME_1
            WHERE COURT_ID = :P53_ID";

    $sql_stmt = $write_db->prepare($sql);

    // Bind parameters

    $sql_stmt->bindParam(':P53_ORDER_NO', $ORDER_NO, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_NAME_OF_COURT', $NAME_OF_COURT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_COURT_FILE_DATE', $COURT_FILE_DATE, PDO::PARAM_STR);
   
    $sql_stmt->bindParam(':P53_INTERIM_ORDER_DATE', $INTERIM_ORDER_DATE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_INTERIM_AMOUNT', $INTERIM_AMOUNT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_JUDGEMENT', $JUDGEMENT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_TYPE', $TYPE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_CHANGE_BANK_ACCOUNT', $CHANGE_BANK_ACCOUNT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_INTERIM_AWARDED_YES', $INTERIM_AWARDED_YES, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BRANCH_NAME_1', $BRANCH_NAME_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BAK_ACCOUNT', $BAK_ACCOUNT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_IFSC_CODE_1', $IFSC_CODE_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_ACCOUNT_NUMBER_1', $ACCOUNT_NUMBER_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BANK_NAME_1', $BANK_NAME_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_ID', $COURT_ID, PDO::PARAM_STR);
    // Execute the query
    if ($sql_stmt->execute()) {
        if ($sql_stmt->rowCount() > 0) {
            $response = ["success" => 1, "message" => "Record updated successfully"];
        } else {
            $response = ["success" => 0, "message" => "No matching record found to update"];
        }
    } else {
        throw new Exception("Database execution error.");
    }
} catch (Exception $e) {
    // Log and handle exceptions
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
