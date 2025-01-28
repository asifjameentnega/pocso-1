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

    // Extract and validate required fields
    $requiredFields = [
        'ORDER_NO', 'COURT_FILE_DATE', 'NAME_OF_COURT', 
        'FINAL_AMOUNT', 'FINAL_ORDER_DATE', 'JUDGEMENT', 
        'TYPE', 'CHANGE_BANK_ACCOUNT', 'INTERIM_AWARDED_YES',
        'BRANCH_NAME_1', 'BAK_ACCOUNT', 'IFSC_CODE_1', 
        'ACCOUNT_NUMBER_1', 'BANK_NAME_1', 'ID'
    ];

    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($inputData[$field])) {
            $missingFields[] = $field;
        }
    }

    // Throw an error if any required field is missing
    // if (!empty($missingFields)) {
    //     throw new Exception("Invalid input. The following fields are required and cannot be null: " . implode(', ', $missingFields));
    // }

    // Extract input data into variables
    $ORDER_NO = $inputData['ORDER_NO'];
    $COURT_FILE_DATE = $inputData['COURT_FILE_DATE'];
    $NAME_OF_COURT = $inputData['NAME_OF_COURT'];
    $FINAL_AMOUNT = $inputData['FINAL_AMOUNT'];
    $FINAL_ORDER_DATE = $inputData['FINAL_ORDER_DATE'];
    $JUDGEMENT = $inputData['JUDGEMENT'];
    $TYPE = $inputData['TYPE'];
    $CHANGE_BANK_ACCOUNT = $inputData['CHANGE_BANK_ACCOUNT'];
    $INTERIM_AWARDED_YES = $inputData['INTERIM_AWARDED_YES'];
    $BRANCH_NAME_1 = $inputData['BRANCH_NAME_1'];
    $BAK_ACCOUNT = $inputData['BAK_ACCOUNT'];
    $IFSC_CODE_1 = $inputData['IFSC_CODE_1'];
    $ACCOUNT_NUMBER_1 = $inputData['ACCOUNT_NUMBER_1'];
    $BANK_NAME_1 = $inputData['BANK_NAME_1'];
    $ID = $inputData['ID'];

    $INTERIM_AMOUNT = $inputData['INTERIM_AMOUNT'] ?? null;
    $INTERIM_ORDER_DATE = $inputData['INTERIM_ORDER_DATE'] ?? null;
    // Check if both INTERIM_AMOUNT and INTERIM_ORDER_DATE are null
    if ($INTERIM_AMOUNT === null && $INTERIM_ORDER_DATE === null) {
        // Prepare the SQL query
        $sql = "UPDATE TNEGA_JUDGE_LOGIN_T
                SET ORDER_NO = :ORDER_NO,
                    COURT_FILE_DATE = :COURT_FILE_DATE,
                    NAME_OF_COURT = :NAME_OF_COURT,
                    FINAL_AMOUNT = :FINAL_AMOUNT,
                    FINAL_ORDER_DATE = :FINAL_ORDER_DATE, 
                    JUDGEMENT = :JUDGEMENT,
                    TYPE = :TYPE,
                    CHANGE_BANK_ACCOUNT = :CHANGE_BANK_ACCOUNT,
                    INTERIM_AWARDED_YES = :INTERIM_AWARDED_YES,
                    BRANCH_NAME = :BRANCH_NAME_1,
                    BAK_ACCOUNT = :BAK_ACCOUNT,
                    IFSC_CODE = :IFSC_CODE_1,
                    ACCOUNT_NUMBER = :ACCOUNT_NUMBER_1,
                    BANK_NAME = :BANK_NAME_1
                WHERE COURT_ID = :ID";

        // Prepare the SQL statement
        $sql_stmt = $write_db->prepare($sql);

        // Bind parameters
        $sql_stmt->bindParam(':ORDER_NO', $ORDER_NO, PDO::PARAM_STR);
        $sql_stmt->bindParam(':COURT_FILE_DATE', $COURT_FILE_DATE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':NAME_OF_COURT', $NAME_OF_COURT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':FINAL_AMOUNT', $FINAL_AMOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':FINAL_ORDER_DATE', $FINAL_ORDER_DATE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':JUDGEMENT', $JUDGEMENT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':TYPE', $TYPE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':CHANGE_BANK_ACCOUNT', $CHANGE_BANK_ACCOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':INTERIM_AWARDED_YES', $INTERIM_AWARDED_YES, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BRANCH_NAME_1', $BRANCH_NAME_1, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BAK_ACCOUNT', $BAK_ACCOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':IFSC_CODE_1', $IFSC_CODE_1, PDO::PARAM_STR);
        $sql_stmt->bindParam(':ACCOUNT_NUMBER_1', $ACCOUNT_NUMBER_1, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BANK_NAME_1', $BANK_NAME_1, PDO::PARAM_STR);
        $sql_stmt->bindParam(':ID', $ID, PDO::PARAM_STR);

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
    } else {
        $response = ["success" => 0, "message" => "INTERIM_AMOUNT or INTERIM_ORDER_DATE is not null, no update performed."];
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
?>
