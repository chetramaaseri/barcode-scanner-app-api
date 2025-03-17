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
        'token' => 'The token field is required.'
    ]);
}

$validations = [];
if(empty($REQUEST['token'])){
    $validations['token'] = 'The token must be a valid string';
}
if(!empty($validations)){
    ApiError::throwValidationError($validations);
}
$token = filter_var($REQUEST['token'], FILTER_SANITIZE_STRING);

try {
    $query = $db->select('u.*, s.scope, s.role')
            ->from('sessions ss')
            ->where('ss.token', $token)
            ->join('INNER', 'users u', 'ss.user_id = u.user_id')
            ->join('LEFT', 'scopes s', 'u.scope_id = s.scope_id')
            ->get();
    $user = $db->row_array($query);
    if(empty($user)){
        ApiError::throw(ApiError::BAD_REQUEST,'Token is Invalid');
    }
    Response::success($user, 'Token is valid');
} catch (\Throwable $th) {
    ApiError::throwInternalServerError($th->getMessage());
}
