<?php
require_once('../../helper/header.php');
require_once('../../helper/encryptDecrypt.php');
require_once('../../config/read_database.php');

// Constant for date format
define('DATE_FORMAT', 'Y-m-d H:i:s.u');


if ($_SERVER["REQUEST_METHOD"] !== 'POST') {
    http_response_code(405);
    $data = array("success" => 0, "message" => "Method Not Allowed");
    echo json_encode($data);
    die();
}
// Decrypt the data
// $decryptedData = isset($_POST['data']) ? decrypt($_POST['data']) : null;
try {
   
    $institution_summary_sql = 'select * from oci_objects_tbl';
    $institution_summary_stmt = $read_db->prepare($institution_summary_sql);

    $institution_summary_stmt->execute();
    $institution_summary_list = $institution_summary_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the results
    if (count($institution_summary_list) > 0) {
        http_response_code(200);
        $data = array("success" => 1, "message" => "Details fetched successfully", 'data' => $institution_summary_list);
        echo json_encode($data);
    } else {
        http_response_code(200);
        $data = array("success" => 0, "message" => "No Data Found", "data" => []);
        echo json_encode($data);
    }

} catch (PDOException $e) {
    http_response_code(400);
    echo json_encode(["success" => 0, "message" => $e->getMessage()]);
    die();
}

