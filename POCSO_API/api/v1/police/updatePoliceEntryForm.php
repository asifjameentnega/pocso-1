<?php

// Load dependencies
require_once('../../../helper/header.php');
require_once('../../../helper/encryptDecrypt.php');
require_once('../../../config/write_database.php');

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
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Validate input data
    $APP_EMPLOYEE_ID = isset($inputData['id']) ? $inputData['id'] : null;
    $CHARGE_SHEET_FIELD = isset($inputData['CHARGE_SHEET_FIELD']) ? $inputData['CHARGE_SHEET_FIELD'] : null;
    $CHARGE_SHEET_DATE = isset($inputData['CHARGE_SHEET_DATE']) ? $inputData['CHARGE_SHEET_DATE'] : null;

    if (!$APP_EMPLOYEE_ID || !in_array($CHARGE_SHEET_FIELD, ['yes', 'no']) || !strtotime($CHARGE_SHEET_DATE)) {
        throw new Exception("Invalid input. Please provide valid 'id', 'CHARGE_SHEET_FIELD' (yes/no), and 'CHARGE_SHEET_DATE' (valid date).");
    }

    // SQL query for updating institutional summary data
    $sql = '
        UPDATE TNEGA_OVERALL_T_DUP
        SET CHARGE_SHEET_YES_NO = :P73_CHARGE_SHEET_FIELD,
            CHARGE_SHEET_DATE = :P73_CHARGE_SHEET_DATE
        WHERE ID = :P73_ID
    ';

    // Prepare statement
    $sql_stmt = $write_db->prepare($sql);
    $sql_stmt->bindParam(':P73_CHARGE_SHEET_FIELD', $CHARGE_SHEET_FIELD, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P73_CHARGE_SHEET_DATE', $CHARGE_SHEET_DATE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':P73_ID', $APP_EMPLOYEE_ID, PDO::PARAM_STR);

    // Execute query
    if ($sql_stmt->execute()) {
        if ($sql_stmt->rowCount() > 0) {
            http_response_code(200); // OK
            $response = ["success" => 1, "message" => "Record updated successfully"];
        } else {
            http_response_code(404); // Not Found
            $response = ["success" => 0, "message" => "No matching record found to update"];
        }
    } else {
        throw new Exception("Database execution error.");
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response = ["success" => 0, "message" => $e->getMessage()];
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);
