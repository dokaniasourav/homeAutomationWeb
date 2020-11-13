<?php
//$start = microtime(true);
require('Lib/function.php');
$out = array("status" => "Unknown", "data" => null);
$conn = DBConnect();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    handlePostAction($conn, $action);
}
// else if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])){
//     $action = $_GET['action'];
//     handleGetAction($conn, $action);
// }
else {
    setOut(501, ["err" => $_REQUEST], 'REQUEST TYPE UNSUPPORTED !');
}
header('content-type: text/html; charset=UTF-8');
echo json_encode($out);
DBDisconnect($conn);
//$time_elapsed_secs = microtime(true) - $start;
//echo $time_elapsed_secs;
