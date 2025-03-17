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
        'mobile' => 'The mobile field is required.',
        'password' => 'The password must be at least 8 characters long.'
    ]);
}

$validations = [];
if(empty($REQUEST['mobile'])){
    $validations['mobile'] = 'The password must be at least 10 characters long';
}
if(empty($REQUEST['password'])){
    $validations['password'] = 'The password must be at least 8 characters long';
}
if(!empty($validations)){
    ApiError::throwValidationError($validations);
}
$mobile = filter_var($REQUEST['mobile'], FILTER_SANITIZE_STRING);
$password = filter_var($REQUEST['password'], FILTER_SANITIZE_STRING);

try {
    $query = $db->select('u.*, s.scope, s.role')
            ->from('users u')
            ->where('u.mobile', $mobile)
            ->join('LEFT', 'scopes s', 'u.scope_id = s.scope_id')
            ->join('LEFT', 'sessions ss', 'u.scope_id = ss.session_id')
            ->get();
    $user = $db->row_array($query);
    if(empty($user)){
        ApiError::throwNotFound('User');
    }else if(md5($password) != $user['password']){
        ApiError::throwUnauthorized('Invalid password. Please check your credentials.');
    }
    $token = md5(uniqid(rand(), true));
    $user['token'] = $token;
    $query = $db->where('user_id', $user['user_id'])->update('sessions', ['token' => $token]);
    if($db->affected_rows($query) == 0){
        $db->insert('sessions', ['token' => $token, 'user_id' => $user['user_id']]);
    }
    Response::success($user, 'Login Successfull');
} catch (\Throwable $th) {
    ApiError::throwInternalServerError($th->getMessage());
}
