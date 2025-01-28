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
    // Get input data
    $inputData = json_decode(file_get_contents("php://input"), true);

    // Required input validation
    $requiredFields = [
        'DISTRICT_NAME', 'POL_STAT', 'COMPLAINT_NAME', 'COMPLAINT_MADE', 'FIR_NO',
        'DATE_OF_FIR', 'CHILD_GENDER', 'CHILD_AGE', 'AGE', 'NAME_PARENT',
        'WILLINGNESS_COMPENSATION', 'WHO_APPLYING', 'WHO_NAME', 'WHO_RELATIONSHIP',
        'WHO_DATE', 'BANK_DETAILS', 'ACCOUNT_HOLDER_NAME', 'ACCOUNT_NUMBER',
        'BANK_NAME', 'BRANCH_NAME', 'IFSC_CODE', 'CHARGE_SHEET_YES_NO',
        'CHARGE_SHEET_DATE', 'NAME_MEDICAL_INSTITUTION', 'DATE_REQUISITION_MEDICAL_EXAMINATION',
        'DATE_OF_MEDICAL_EXAMINATION_VICTIM', 'DATE_OF_INTERIM'
    ];

    // foreach ($requiredFields as $field) {
    //     if (!isset($inputData[$field]) || empty($inputData[$field])) {
    //         throw new Exception("Missing or empty required field: $field");
    //     }
    // }

    // Extract variables for binding
    $DISTRICT_NAME = $inputData['DISTRICT_NAME'];
    $POL_STAT = $inputData['POL_STAT'];
    $COMPLAINT_NAME = $inputData['COMPLAINT_NAME'];
    $COMPLAINT_MADE = $inputData['COMPLAINT_MADE'];
    $FIR_NO = $inputData['FIR_NO'];
    $DATE_OF_FIR = $inputData['DATE_OF_FIR'];
    $CHILD_GENDER = $inputData['CHILD_GENDER'];
    $CHILD_AGE =$inputData['CHILD_AGE'];
    $AGE = (int)$inputData['AGE'];
    $NAME_PARENT = $inputData['NAME_PARENT'];
    $WILLINGNESS_COMPENSATION = $inputData['WILLINGNESS_COMPENSATION'];
    
    $WHO_APPLYING = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['WHO_APPLYING'];
    $WHO_NAME = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['WHO_NAME'];
    $WHO_RELATIONSHIP = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['WHO_RELATIONSHIP'];
    $WHO_DATE = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['WHO_DATE'];
    $BANK_DETAILS = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['BANK_DETAILS'];
    $ACCOUNT_HOLDER_NAME = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['ACCOUNT_HOLDER_NAME'];
    $ACCOUNT_NUMBER = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['ACCOUNT_NUMBER'];
    $BANK_NAME = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['BANK_NAME'];
    $BRANCH_NAME = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['BRANCH_NAME'];
    $IFSC_CODE = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['IFSC_CODE'];
    // $created_at = date('Y-m-d H:i:s'); 
    $CHARGE_SHEET_YES_NO = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['CHARGE_SHEET_YES_NO'];
    $CHARGE_SHEET_DATE = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['CHARGE_SHEET_DATE'];
    $NAME_MEDICAL_INSTITUTION = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['NAME_MEDICAL_INSTITUTION'];
    $DATE_REQUISITION_MEDICAL_EXAMINATION = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['DATE_REQUISITION_MEDICAL_EXAMINATION'];
    $DATE_OF_MEDICAL_EXAMINATION_VICTIM = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['DATE_OF_MEDICAL_EXAMINATION_VICTIM'];
    $DATE_OF_INTERIM = $WILLINGNESS_COMPENSATION == 'No' ? null : $inputData['DATE_OF_INTERIM'];

    // Validate date fields
    foreach (['DATE_OF_FIR', 'WHO_DATE', 'CHARGE_SHEET_DATE', 'DATE_REQUISITION_MEDICAL_EXAMINATION', 'DATE_OF_MEDICAL_EXAMINATION_VICTIM', 'DATE_OF_INTERIM'] as $dateField) {
        if (isset($inputData[$dateField]) && !empty($inputData[$dateField]) && !DateTime::createFromFormat('Y-m-d', $inputData[$dateField])) {
            throw new Exception("Invalid date format for field: $dateField. Expected format: YYYY-MM-DD");
        }
    }

    // SQL query for insertion
    $sql = '
        INSERT INTO TNEGA_OVERALL_T_DUP (
            DISTRICT_NAME, POL_STAT, COMPLAINT_NAME, COMPLAINT_MADE, FIR_NO, DATE_OF_FIR, CHILD_GENDER,
            CHILD_AGE, AGE, NAME_PARENT, WILLINGNESS_COMPENSATION, WHO_APPLYING, WHO_NAME,
            WHO_RELATIONSHIP, WHO_DATE, BANK_DETAILS, ACCOUNT_HOLDER_NAME, ACCOUNT_NUMBER, BANK_NAME,
            BRANCH_NAME, IFSC_CODE, CHARGE_SHEET_YES_NO, CHARGE_SHEET_DATE, NAME_MEDICAL_INSTITUTION,
            DATE_REQUISITION_MEDICAL_EXAMINATION, DATE_OF_MEDICAL_EXAMINATION_VICTIM, DATE_OF_INTERIM
        ) VALUES (
            :DISTRICT_NAME, :POL_STAT, :COMPLAINT_NAME, :COMPLAINT_MADE, :FIR_NO, :DATE_OF_FIR, :CHILD_GENDER,
            :CHILD_AGE, :AGE, :NAME_PARENT, :WILLINGNESS_COMPENSATION, :WHO_APPLYING, :WHO_NAME,
            :WHO_RELATIONSHIP, :WHO_DATE, :BANK_DETAILS, :ACCOUNT_HOLDER_NAME, :ACCOUNT_NUMBER, :BANK_NAME,
            :BRANCH_NAME, :IFSC_CODE, :CHARGE_SHEET_YES_NO, :CHARGE_SHEET_DATE, :NAME_MEDICAL_INSTITUTION,
            :DATE_REQUISITION_MEDICAL_EXAMINATION, :DATE_OF_MEDICAL_EXAMINATION_VICTIM, :DATE_OF_INTERIM
        )
    ';

    // Prepare statement
    $sql_stmt = $write_db->prepare($sql);

    // Bind parameters
    $sql_stmt->bindParam(':DISTRICT_NAME', $DISTRICT_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':POL_STAT', $POL_STAT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':COMPLAINT_NAME', $COMPLAINT_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':COMPLAINT_MADE', $COMPLAINT_MADE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':FIR_NO', $FIR_NO, PDO::PARAM_STR);
    $sql_stmt->bindParam(':DATE_OF_FIR', $DATE_OF_FIR, PDO::PARAM_STR);
    $sql_stmt->bindParam(':CHILD_GENDER', $CHILD_GENDER, PDO::PARAM_STR);
    $sql_stmt->bindParam(':CHILD_AGE', $CHILD_AGE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':AGE', $AGE, PDO::PARAM_INT);
    $sql_stmt->bindParam(':NAME_PARENT', $NAME_PARENT, PDO::PARAM_STR);
    $sql_stmt->bindParam(':WILLINGNESS_COMPENSATION', $WILLINGNESS_COMPENSATION, PDO::PARAM_STR);
    $sql_stmt->bindParam(':WHO_APPLYING', $WHO_APPLYING, PDO::PARAM_STR);
    $sql_stmt->bindParam(':WHO_NAME', $WHO_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':WHO_RELATIONSHIP', $WHO_RELATIONSHIP, PDO::PARAM_STR);
    $sql_stmt->bindParam(':WHO_DATE', $WHO_DATE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':BANK_DETAILS', $BANK_DETAILS, PDO::PARAM_STR);
    $sql_stmt->bindParam(':ACCOUNT_HOLDER_NAME', $ACCOUNT_HOLDER_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':ACCOUNT_NUMBER', $ACCOUNT_NUMBER, PDO::PARAM_STR);
    $sql_stmt->bindParam(':BANK_NAME', $BANK_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':BRANCH_NAME', $BRANCH_NAME, PDO::PARAM_STR);
    $sql_stmt->bindParam(':IFSC_CODE', $IFSC_CODE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':CHARGE_SHEET_YES_NO', $CHARGE_SHEET_YES_NO, PDO::PARAM_STR);
    $sql_stmt->bindParam(':CHARGE_SHEET_DATE', $CHARGE_SHEET_DATE, PDO::PARAM_STR);
    $sql_stmt->bindParam(':NAME_MEDICAL_INSTITUTION', $NAME_MEDICAL_INSTITUTION, PDO::PARAM_STR);
    $sql_stmt->bindParam(':DATE_REQUISITION_MEDICAL_EXAMINATION', $DATE_REQUISITION_MEDICAL_EXAMINATION, PDO::PARAM_STR);
    $sql_stmt->bindParam(':DATE_OF_MEDICAL_EXAMINATION_VICTIM', $DATE_OF_MEDICAL_EXAMINATION_VICTIM, PDO::PARAM_STR);
    $sql_stmt->bindParam(':DATE_OF_INTERIM', $DATE_OF_INTERIM, PDO::PARAM_STR);
    // $sql_stmt->bindParam(':CREATED_DATE', $created_at, PDO::PARAM_STR);

    // Execute query
    if ($sql_stmt->execute()) {
        // Fetch last inserted ID using created_at timestamp
        $select_sql = "SELECT id FROM TNEGA_OVERALL_T_DUP ORDER BY CREATED_DATE DESC LIMIT 1";
        $select_stmt = $write_db->prepare($select_sql);
        $select_stmt->execute();
        
        // Fetch the result and get the last inserted ID
        $select_row = $select_stmt->fetch(PDO::FETCH_ASSOC);
        $last_id = $select_row['id']; // Fetching the id from the result row
        
        $response = [
            "success" => 1,
            "message" => "Record inserted successfully",
            "return_id" => $last_id
        ];
    } else {
        throw new Exception("Database execution error.");
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response = ["success" => 0, "message" => $e->getMessage()];
  //  echo json_encode($response);
}

// Output response
header('Content-Type: application/json');
echo json_encode($response);
?>
