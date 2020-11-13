<?php

function Err($e)
{
    global $error;
    $error = $error . $e . " \r\n ";
}

function print_Associate($arr)
{
    foreach ($arr as $key => $value) {
        printf("<br>\t%s=>[ %s]  ", $key, $value);
    }
}

function redirect($url, $statusCode = 301)
{
    header('Location: ' . $url, true, $statusCode);
    die();
}

function transformArray($prams, $table = null)
{
    global $columnNames;
    $tablePrams = array();
    if ($table === null) {
        $table = $columnNames;
    }
    if ($prams === null) {
        return $tablePrams;
    }
    foreach ($prams as $key => $val) {                                             //Transpose Array elements
        if (isset($table[$key]) || array_key_exists($key, $table)) {
            $tablePrams[$table[$key]] = $val;
        }
    }
    return $tablePrams;
}


function checkPrams(int $method, string $action)
{
    global $rangeAllowed, $actionPairPost, $actionPairGet;
    $globalArrayName    = [];
    $globalRequestName  = $_POST;
    $iteration_field = 0;
    if ($method === POST) {
        $globalArrayName = $actionPairPost;
        $iteration_field = 3;
    } else if ($method === GET) {
        $globalArrayName = $actionPairGet;
        $iteration_field = 2;
    } else {
        setOut(501, [], "Only POST or GET requests are allowed");
        return false;
    }
    if ($globalArrayName !== []) {
        if (isset($globalArrayName[$action])) {
            for ($i = 0; $i < $iteration_field; $i++) {
                foreach ($globalArrayName[$action][$i] as $key) {
                    if (!isset($globalRequestName[$key]) || strlen($globalRequestName[$key]) === 0) {
                        setOut(402, ['err' => ['prams' => $globalRequestName]], 'Incomplete or Incorrect Parameters');
                        return false;
                    }
                    if (array_key_exists($key, $rangeAllowed)) {
                        if (!is_numeric($globalRequestName[$key])) {
                            setOut(
                                403,
                                ['err' => ['prams' => $globalRequestName]],
                                $key . ' should be a number'
                            );
                            return false;
                        }
                        if (!(($globalRequestName[$key] >= $rangeAllowed[$key][0]) &&
                            ($globalRequestName[$key] <= $rangeAllowed[$key][1]))) {
                            setOut(
                                403,
                                ['err' => ['prams' => $globalRequestName]],
                                $key . ' is out of range. Should be between ' . $rangeAllowed[$key][0] .
                                    ' and ' . $rangeAllowed[$key][1]
                            );
                            return false;
                        }
                    }
                }
            }
            return true;
        } else {
            setOut(405, [], "This method has not been implemented yet");
            return false;
        }
    }
    setOut(500, [], "Bad request");
    return false;
}

function getTableName(string $action)
{
    if (substr($action, 3, strlen('Mod')) === 'Mod') {
        return "modules";
    } else if (substr($action, 3, strlen('Dev')) === 'Dev') {
        $modID = $_REQUEST['modID'];
        return sprintf("module_%05d", $modID);
    } else if ($action === "setUserName" || $action === "setUserPass") {
        return "users";
    } else if (substr($action, 3, strlen('User')) === 'User') {
        return "user_detail";
    } else {
        return null;
    }
}

function getRequestPrams($request, $keys)
{
    $prams = array();
    if (count($keys) !== 0) {
        $source = null;
        if ($request === DUMMY) {
            foreach ($keys as $key) {
                $prams[$key] = 'xxx';
            }
        } else {
            $source = $_POST;
            // if ($request === POST) {
            //     $source = $_POST;
            // } else if ($request === GET) {
            //     $source = $_GET;
            // } else if ($request === PUT) {
            //     $source = $_REQUEST;
            // }

            foreach ($keys as $key) {
                if (!isset($source[$key])) {
                    Err('The parameter ' . $key . ' not found in the list');
                } else {
                    $prams[$key] = $source[$key];
                }
            }
        }
    }
    return $prams;
}

function extractIntPram($output, string $pram): int
{
    global $columnNames;
    if (
        isset($columnNames[$pram]) &&
        isset($output[$columnNames[$pram]]) &&
        is_numeric($output[$columnNames[$pram]])
    ) {
        return (int) $output[$columnNames[$pram]];
    }
    return -1;
}
