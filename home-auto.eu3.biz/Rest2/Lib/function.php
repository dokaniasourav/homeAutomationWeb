<?php

declare(strict_types=1);
require('data.php');
require('DBFunctions.php');
require('browser.php');
require('device.php');
require('helper.php');
require('module.php');
require('user.php');

function handlePostAction(mysqli $conn, string $action)
{
    $match_act1 = substr($action, 0, 3);
    $match_act2 = substr($action, 3, 3);
    $method = POST;

    if ($match_act1 === "get") {
        $method = GET;
    }

    //    if($_POST[])

    if (!checkPrams($method, $action)) {
        return false;
    }
    if ($action === 'login') {
        login($conn);
    } else if ($action === 'addModule') {
        addModule($conn, $action);
    } else if ($action === 'addDevice') {
        addDevice($conn, $action);
    } else if ($action === 'removeDevice') {
        removeDevice($conn, $action);
    } else if ($action === 'removeModule') {
        removeModule($conn, $action);
    } else if ($action === 'signOut') {
        signOut($conn);
    } else if ($action === 'addUser') {
        addUser($conn);
    }

    if ($match_act1 === "get") {
        if ($match_act2 === 'Dev') {
            getDevice($conn, $action);
        } else if ($match_act2 === 'Mod') {
            getModule($conn, $action);
        } else if ($match_act2 === 'Use') {
            getUser($conn, $action);
        }
    } else if ($match_act1 === "set") {
        setValues($conn, $action);
    } else {
        return false;
    }
    return true;
}

// function handleGetAction(mysqli $conn, string $action)
// {
//     if (!checkPrams(GET, $action)) {
//         return false;
//     }
//     $match_act = substr($action, 3, 3);
//     if ($match_act === 'Dev') {
//         getDevice($conn, $action);
//     } else if ($match_act === 'Mod') {
//         getModule($conn, $action);
//     } else if ($match_act === 'Use') {
//         getUser($conn, $action);
//     }
//     return true;
// }

function setValues(mysqli $conn, $action)
{
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairPost;
    $keys_set = $actionPairPost[$action][SET_P];                            //Get action pair
    $set_pram = getRequestPrams(POST, $keys_set);
    $keys_match = $actionPairPost[$action][MATCH];                            //Get action pair
    $match_pram = getRequestPrams(POST, $keys_match);
    $table_name = "";
    $match_act = substr($action, 3, 3);
    if ($match_act === 'Use') {
        $table_name = USER_DETAILS_TABLE;
    } else if (checkAuthorisation($conn, (int) $match_pram['modID'], $id) !== null) {
        $set_pram['up_by'] = $id;
        if ($match_act === 'Mod') {
            $table_name = MODULES_TABLE;
        } else if ($match_act === 'Dev') {
            $table_name = DEVICES_TABLE;
        }
    } else {
        setOut(401, [
            'set' => $set_pram,
            'for' => $match_pram
        ], "You do not have access to this or these module");
        return false;
    }
    if (!empty($table_name)) {
        if ($match_act === 'Dev') {
            $match_pram['active'] = '1';
            if ($action === 'setDevState') {
                $set_pram['up_at'] = date('Y-m-d H:i:s', (int) $set_pram['up_at']);
            }
        }
        $qry = buildQuery($conn, UPDATE, $table_name, $match_pram, $set_pram);
        if ($match_act !== 'Use') {
            unset($set_pram['up_by']);
        }
        if ($match_act === 'Dev') {
            unset($match_pram['active']);
        }
        $qry_res = query_exec($conn, $qry);
        if ($qry_res === 1) {
            setOut(202, ['set' => $set_pram, 'for' => $match_pram], 'Values were updated successfully');
            return true;
        }
        if ($qry_res === 100) {
            setOut(202, ['set' => $set_pram, 'for' => $match_pram], 'No values were affected');
            return true;
        }
        setOut(500, ['set' => $set_pram, 'for' => $match_pram], 'Something went wrong');
    } else {
        setOut(401, [
            'set' => $set_pram,
            'for' => $match_pram
        ], "Unauthorised or Non-existent Value");
    }
    return false;
}
