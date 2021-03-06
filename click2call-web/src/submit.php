<?php
require('../vendor/autoload.php');
$api = new \CALLR\API\Client;

if(($_POST['callr_login'] & $_POST['callr_password'] & $_POST['callr_target']) == ""){
     if((getenv('CALLR_LOGIN') & getenv('CALLR_PASS') & getenv('CALLR_TARGET')) == ""){
        echo 'No credentials found, unable to process request';
     } else {
        $api->setAuthCredentials(getenv('CALLR_LOGIN'), getenv('CALLR_PASS'));
        $target_number = getenv('CALLR_TARGET');
     }
} else {
        $api->setAuthCredentials($_POST['callr_login'], $_POST['callr_password']);
        $target_number = $_POST['callr_target'];
}

$client_phone = new stdClass;
$client_phone->number = $_POST['customer_phone'];
$client_phone->timeout = 30;

$target = new stdClass;
$target->number = $target_number;
$target->timeout = 30;

$result = new stdClass;
try {
    $appId = "";
    if(getenv('APP_ID') == "") {
        // no application id defined, check for id in cache file.
        if(file_exists('click2call.appid')) {
            $appId = file_get_contents('click2call.appid');
        } 
        // file not found or ID not valid
        if(strlen($appId) == 0){
            $c2cApp = $api->call('apps.create', ["CLICKTOCALL10","connect_us_web", NULL]);
            $appId = $c2cApp->hash;
            file_put_contents('click2call.appid', $appId);
        }
    } else {
        // reuse defined app id
        $appId = getenv('APP_ID');
    }
    $call_id = $api->call('clicktocall/calls.start_2', [$appId,[$client_phone],[$target], NULL]);
    $result->ok = "Your call is being connected! (ID:{$call_id})";
} catch(Exception $e){
    if($e->getCode() == 22){
        $result->error = "Exception: Authentication failure";    
    } else {
        $result->error = "Exception: Click to call failed\r\n";
    }
    $result->errorcode = "Code: {$e->getCode()}";
    $result->errormsg = "Message {$e->getMessage()}";
    $result->errortrace = "Trace: {$e->getTraceAsString()}";
} finally {
    print_r(json_encode($result));
    exit;
}
?>