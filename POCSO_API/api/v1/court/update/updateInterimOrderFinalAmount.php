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
        'FINAL_ORDER_DATE', 'FINAL_AMOUNT', 'JUDGEMENT', 
        'TYPE', 'CHANGE_BANK_ACCOUNT', 'INTERIM_AWARDED_YES',
        'BRANCH_NAME', 'BAK_ACCOUNT', 'IFSC_CODE', 
        'ACCOUNT_NUMBER', 'BANK_NAME', 'ID'
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
    $FINAL_ORDER_DATE = $inputData['FINAL_ORDER_DATE'];
    $FINAL_AMOUNT = $inputData['FINAL_AMOUNT'];
    $JUDGEMENT = $inputData['JUDGEMENT'];
    $TYPE = $inputData['TYPE'];
    $CHANGE_BANK_ACCOUNT = $inputData['CHANGE_BANK_ACCOUNT'];
    $INTERIM_AWARDED_YES = $inputData['INTERIM_AWARDED_YES'];
    $BRANCH_NAME = $inputData['BRANCH_NAME'];
    $BAK_ACCOUNT = $inputData['BAK_ACCOUNT'];
    $IFSC_CODE = $inputData['IFSC_CODE'];
    $ACCOUNT_NUMBER = $inputData['ACCOUNT_NUMBER'];
    $BANK_NAME = $inputData['BANK_NAME'];
    $ID = $inputData['ID'];

    // Check if FINAL_AMOUNT is null and FINAL_ORDER_DATE is not null
    if ($FINAL_AMOUNT == null && $FINAL_ORDER_DATE != null) {
        // Prepare the SQL query
        $sql = "UPDATE TNEGA_JUDGE_LOGIN_T
                SET ORDER_NO = :ORDER_NO,
                    COURT_FILE_DATE = :COURT_FILE_DATE,
                    NAME_OF_COURT = :NAME_OF_COURT,
                    FINAL_ORDER_DATE = :FINAL_ORDER_DATE,
                    FINAL_AMOUNT = :FINAL_AMOUNT,
                    JUDGEMENT = :JUDGEMENT,
                    TYPE = :TYPE,
                    CHANGE_BANK_ACCOUNT = :CHANGE_BANK_ACCOUNT,
                    INTERIM_AWARDED_YES = :INTERIM_AWARDED_YES,
                    BRANCH_NAME = :BRANCH_NAME,
                    BAK_ACCOUNT = :BAK_ACCOUNT,
                    IFSC_CODE = :IFSC_CODE,
                    ACCOUNT_NUMBER = :ACCOUNT_NUMBER,
                    BANK_NAME = :BANK_NAME
                WHERE COURT_ID = :ID";

        // Prepare the SQL statement
        $sql_stmt = $write_db->prepare($sql);

        // Bind parameters
        $sql_stmt->bindParam(':ORDER_NO', $ORDER_NO, PDO::PARAM_STR);
        $sql_stmt->bindParam(':COURT_FILE_DATE', $COURT_FILE_DATE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':NAME_OF_COURT', $NAME_OF_COURT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':FINAL_ORDER_DATE', $FINAL_ORDER_DATE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':FINAL_AMOUNT', $FINAL_AMOUNT, PDO::PARAM_NULL);
        $sql_stmt->bindParam(':JUDGEMENT', $JUDGEMENT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':TYPE', $TYPE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':CHANGE_BANK_ACCOUNT', $CHANGE_BANK_ACCOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':INTERIM_AWARDED_YES', $INTERIM_AWARDED_YES, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BRANCH_NAME', $BRANCH_NAME, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BAK_ACCOUNT', $BAK_ACCOUNT, PDO::PARAM_STR);
        $sql_stmt->bindParam(':IFSC_CODE', $IFSC_CODE, PDO::PARAM_STR);
        $sql_stmt->bindParam(':ACCOUNT_NUMBER', $ACCOUNT_NUMBER, PDO::PARAM_STR);
        $sql_stmt->bindParam(':BANK_NAME', $BANK_NAME, PDO::PARAM_STR);
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
        $response = ["success" => 0, "message" => "FINAL_AMOUNT or FINAL_ORDER_DATE condition not met, no update performed."];
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
