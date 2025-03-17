<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once dirname(dirname(__FILE__)) . '/core/Db.php';
require_once dirname(dirname(__FILE__)) . '/core/ApiError.php';
require_once dirname(dirname(__FILE__)) . '/core/Response.php';

$db = new Db();
if($_SERVER['REQUEST_METHOD'] != 'POST'){
    ApiError::throwMethodNotAllowed();
}
$REQUEST = $_POST;
if(empty($_POST)){
    $REQUEST = file_get_contents('php://input');
    $REQUEST = json_decode($REQUEST, true) ?? [];
}

if(empty($REQUEST)){
    ApiError::throwValidationError([
        'user_id' => 'The user_id field is required.',
        'barcode' => 'The barcode field is required.'
    ]);
}

$validations = [];
if(empty($REQUEST['user_id'])){
    $validations['user_id'] = 'Provide a valid user id';
}
if(empty($REQUEST['barcode'])){
    $validations['barcode'] = 'The barcode must be a valid string';
}
if(!empty($validations)){
    ApiError::throwValidationError($validations);
}
$user_id = filter_var($REQUEST['user_id'], FILTER_SANITIZE_STRING);
$barcode = filter_var($REQUEST['barcode'], FILTER_SANITIZE_STRING);
$timezone = new DateTimeZone('Asia/Kolkata');
$today = new DateTime(null,$timezone);
try {
    $scanned = [
        "item_code" => $barcode,
        "user_id" => $user_id,
        "created_at" => $today->format('Y-m-d h:i:s'),
        "updated_at" => $today->format('Y-m-d h:i:s')
    ];
    $log_id = $db->insert('checker_log',$scanned);
    $startDate = new \DateTime(null,$timezone);
    $startDate->setTime(0, 0, 0);
    $endDate = new \DateTime(null,$timezone);
    $query = $db->select('COUNT(*) as total_scanned')
                ->from('checker_log')
                ->where('user_id', $user_id)
                ->where('created_at >=', $startDate->format('Y-m-d H:i:s'))
                ->where('created_at <', $endDate->format('Y-m-d H:i:s'))
                ->get();
    $result = $db->row_array($query);
    if(empty($log_id) || empty($result)){
        ApiError::throwInternalServerError();
    }
    sleep(2);
    Response::success(['log_id' => $log_id, 'total_scanned' => $result['total_scanned']], 'Entry Added Successfully');
} catch (\Throwable $th) {
    ApiError::throwInternalServerError($th->getMessage());
}
