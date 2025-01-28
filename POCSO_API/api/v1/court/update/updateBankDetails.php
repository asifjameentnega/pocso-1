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
    $requiredFields = ['COURT_ID'];
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

    // Assign validated input data to variables
    $COURT_ID = $inputData['COURT_ID'];
    $IFSC_CODE = $inputData['IFSC_CODE'] ?? null;
    $IFSC_CODE_1 = $inputData['IFSC_CODE_1'] ?? null;
    $BANK_NAME = $inputData['BANK_NAME'] ?? null;
    $BANK_NAME_1 = $inputData['BANK_NAME_1'] ?? null;
    $BRANCH_NAME = $inputData['BRANCH_NAME'] ?? null;
    $BRANCH_NAME_1 = $inputData['BRANCH_NAME_1'] ?? null;
    $ACCOUNT_NUMBER = $inputData['ACCOUNT_NUMBER'] ?? null;
    $ACCOUNT_NUMBER_1 = $inputData['ACCOUNT_NUMBER_1'] ?? null;
    $ACCOUNT_HOLDER_NAME = $inputData['ACCOUNT_HOLDER_NAME'] ?? null;
    $BAK_ACCOUNT = $inputData['BAK_ACCOUNT'] ?? null;

    // Prepare SQL query
    $sql = "UPDATE TNEGA_OVERALL_T_DUP
            SET IFSC_CODE = COALESCE(:P53_IFSC_CODE_1, :P53_IFSC_CODE),
                BANK_NAME = COALESCE(:P53_BANK_NAME_1, :P53_BANK_NAME), 
                BRANCH_NAME = COALESCE(:P53_BRANCH_NAME_1, :P53_BRANCH_NAME),
                ACCOUNT_NUMBER = COALESCE(:P53_ACCOUNT_NUMBER_1, :P53_ACCOUNT_NUMBER),
                ACCOUNT_HOLDER_NAME = COALESCE(:P53_BAK_ACCOUNT, :P53_ACCOUNT_HOLDER_NAME),
                REQUEST_STATUS = 'S'
            WHERE ID = :P53_COURT_ID";

    $sql_stmt = $write_db->prepare($sql);

    // Bind parameters
    $sql_stmt->bindParam(':P53_IFSC_CODE', $IFSC_CODE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_IFSC_CODE_1', $IFSC_CODE_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BANK_NAME', $BANK_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BANK_NAME_1', $BANK_NAME_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BRANCH_NAME', $BRANCH_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BRANCH_NAME_1', $BRANCH_NAME_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_ACCOUNT_NUMBER', $ACCOUNT_NUMBER, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_ACCOUNT_NUMBER_1', $ACCOUNT_NUMBER_1, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_ACCOUNT_HOLDER_NAME', $ACCOUNT_HOLDER_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_BAK_ACCOUNT', $BAK_ACCOUNT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P53_COURT_ID', $COURT_ID, PDO::PARAM_STR);

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
