<?php
chdir(dirname(__FILE__));
ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

include_once 'includes/main/WebUI.php';
include_once 'mobileapi/Utils.php';

$path = trim($_SERVER['REQUEST_URI'], '/');
$parts = explode('/', $path);
$assignedUserId = 1;
global $current_user;
$userid = 1;
$seed_user = new Users();
$current_user = $seed_user->retrieveCurrentUserInfoFromFile($userid);
//echo "<pre>//";
// Example: /api/users/19x1
if ($parts[0] === 'api' && $parts[1] === 'users' && isset($parts[2])) {
    $recordId = $parts[2];
    include_once 'modules/Users/Users.php';
    $user = new Users();
    $user->retrieve_entity_info($recordId, 'Users');
    
    echo json_encode($user->column_fields);
}else if ($parts[0] === 'api' && $parts[1] === 'contacts' && isset($parts[2])) {
    $crmid = $parts[2];
    $module = $parts[1];

   $reponse =  getRecordDetails($crmid,$module);
    echo "<pre>";
    print_r($reponse);
    echo json_encode($reponse);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid endpoint']);
}
