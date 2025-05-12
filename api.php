<?php
chdir(dirname(__FILE__));
// Set content type
header("Content-Type: application/json");
//ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING

include_once 'includes/main/WebUI.php';
include_once 'mobileapi/Utils.php';
//API  user check before any action
global $current_user;
// Decode the JSON data
$data = getInputData();
if (isset($data['user_id'])) {
    $userid = $data['user_id'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user ID']);
    exit;
}
$seed_user = new Users();
$current_user = $seed_user->retrieveCurrentUserInfoFromFile($userid);

// Parse URL
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$parts = explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'));

// Utilities
function getInputData() {
    return json_decode(file_get_contents("php://input"), true);
}

// Handle Users and Contacts
$module = isset($parts[1]) ? ucfirst($parts[1]) : null;
$id = isset($parts[2]) ? $parts[2] : null;


$type =  $data['type'] ?? null;
if ($parts[0] === 'api') {
    switch ($method) {
        case 'GET':
            if($type == 'moduleslist'){
                // Get enabled modules list
                $response = getEnabledModulesList();
            }
            else if ($type == 'details') {
                // Get record details
                $response = getRecordDetails($id,$module);
            }
            else if ($type == 'list' || $type == 'recordlist') {
                // Get all records
                $cvid = $data['cvid'] ?? null;
                $page = $data['page'] ?? 0 ;
                $pageLimit =  $data['pageLimit'] ?? 20;
                $response = getAllRecords($module,$userid,$cvid,$page, $pageLimit);

            }
            else if ($type == 'customviews') {
                // Get record details
               $response = getCustomViews($module);

            }
            else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid type']);
                exit;
            }
            echo json_encode($response);
            break;

        case 'POST':
           
            $record = new $module();
            foreach ($data as $key => $value) {
                $record->column_fields[$key] = $value;
            }
            $record->save($module);
            echo json_encode(['message' => "$module created", 'id' => $record->id]);
            break;

        case 'PUT':
            if ($id) {
                $record = new $module();
                $record->retrieve_entity_info($id, $module);
                foreach ($data as $key => $value) {
                    $record->column_fields[$key] = $value;
                }
                $record->save($module);
                echo json_encode(['message' => "$module updated", 'id' => $record->id]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing record ID for update']);
            }
            break;

        case 'DELETE':
            if ($id) {
                require_once 'include/utils/DeleteUtils.php';
                deleteSingleRecord($module, $id);
                echo json_encode(['message' => "$module deleted", 'id' => $id]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Missing record ID for delete']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} else {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid endpoint']);
}
