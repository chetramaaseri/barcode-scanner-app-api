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
        'date_range' => 'The date_range field is required.',
    ]);
}

$validations = [];
if(empty($REQUEST['user_id'])){
    $validations['user_id'] = 'Provide a valid user id';
}
if(empty($REQUEST['date_range']) || count($REQUEST['date_range']) < 2){
    $validations['date_range'] = 'The date_range must be a valid array of timestamp';
}
if(!empty($validations)){
    ApiError::throwValidationError($validations);
}
$timezone = new DateTimeZone('Asia/Kolkata');
$timezoneUTC = new DateTimeZone('UTC');
$today = new \DateTime(null, $timezone);

$user_id = filter_var($REQUEST['user_id'], FILTER_SANITIZE_STRING);

$date_range1 = filter_var($REQUEST['date_range'][0], FILTER_SANITIZE_STRING);
$date_range1 = new \DateTime($date_range1,$timezoneUTC);
$date_range1->setTimezone($timezone);

$date_range2 = filter_var($REQUEST['date_range'][1], FILTER_SANITIZE_STRING);
$date_range2 = new \DateTime($date_range2,$timezoneUTC);
$date_range2->setTimezone($timezone);

try {
    $query = $db->select('COUNT(*) as total_scanned')
                ->from('checker_log')
                ->where('user_id', $user_id)
                ->where('created_at >=', $date_range1->format('Y-m-d H:i:s'))
                ->where('created_at <', $date_range2->format('Y-m-d H:i:s'))
                ->get();
    $result = $db->row_array($query);
    if(empty($result)){
        ApiError::throwInternalServerError();
    }
    Response::success(['total_scanned' => $result['total_scanned']], 'Data Retrieved Successfully');
} catch (\Throwable $th) {
    ApiError::throwInternalServerError($th->getMessage());
}
