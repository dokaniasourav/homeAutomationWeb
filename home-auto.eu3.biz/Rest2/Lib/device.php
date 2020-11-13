<?php

function getDevice(mysqli $conn, string $action)
{
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairGet, $columnNames;
    $keys_get = $actionPairGet[$action][GET_P];                            //Get action pair
    $get_pram = getRequestPrams(DUMMY, $keys_get);
    $keys_match = $actionPairGet[$action][MATCH];                          //Get action pair
    $match_pram = getRequestPrams(GET, $keys_match);

    $modID = mysqli_real_escape_string($conn, $match_pram['modID']);
    if (checkAuthorisation($conn, $modID, $id) === null) {
        setOut(401, [
            'get' => array_keys($get_pram),
            'for' => $match_pram,
        ], 'Unauthorised, Access denied');
        return false;
    }
    $qry = 'CALL countRowsOut(' . $modID . ')';
    $out_value = getResultFromQuery($conn, $qry, PROCEDURE);
    if ($out_value === null || $out_value[0]['COUNT'] === 0) {
        setOut(411, [
            'get' => array_keys($get_pram),
            'for' => $match_pram
        ], 'No devices present in module, Something went wrong');
        return false;
    }
    if ($action !== 'getDevEverything') {
        $match_pram['active'] = 1;
    }
    $qry = buildQuery(
        $conn,
        SELECT,
        DEVICES_TABLE,
        $match_pram,
        $get_pram
    );
    $out_value = getResultFromQuery($conn, $qry);
    $onlyActive = false;
    if (isset($match_pram['active'])) {
        unset($match_pram['active']);
        $onlyActive = true;
    }

    if ($out_value !== null && count($out_value) !== 0) {
        $out_value_t = [];
        foreach ($out_value as $out) {
            $out_value_t[] = transformArray($out, array_flip($columnNames));
        }
        setOut(200, [
            'get' => array_keys($get_pram),
            'for' => $match_pram,
            'out' => $out_value_t
        ]);
    } else if ($onlyActive === true) {
        setOut(404, [
            'get' => array_keys($get_pram),
            'for' => $match_pram
        ], 'This device is not active yet');
    } else {
        setOut(411, [
            'get' => array_keys($get_pram),
            'for' => $match_pram
        ], 'You don\'t not have any devices yet');
    }
    return false;
}

function addDevice(mysqli $conn, string $action)
{
    $login = checkLogin($conn); // Check If user valid
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairPost;
    $keys_set = $actionPairPost[$action][SET_P];
    $keys_match = $actionPairPost[$action][MATCH];
    $set_pram = getRequestPrams(POST, $keys_set);
    $match_pram = getRequestPrams(POST, $keys_match);
    $modID = mysqli_real_escape_string($conn, $match_pram['modID']);
    if (checkAuthorisation($conn, $modID, $id) === null) {
        setOut(401, ['set' => $set_pram, 'for' => $match_pram], 'Unauthorised, Cannot add device');
        return false;
    }
    $devID = mysqli_real_escape_string($conn, $set_pram['devID']);
    $type  = mysqli_real_escape_string($conn, $set_pram['devType']);
    $qry = sprintf('CALL `deviceAdder`(%d, %d, %d)', $modID, $devID, $type);
    $status = query_exec($conn, $qry);
    if ($status === 1) {
        setOut(201, ['set' => $set_pram, 'for' => $match_pram], 'Device Inserted Successfully');
        return true;
    } else if ($status === 1062) {
        setOut(409, ['set' => $set_pram, 'for' => $match_pram], 'Duplicate Entry for this device ID');
        return false;
    }
    setOut(500, [], 'Error Creating Device');
    return false;
}

function removeDevice(mysqli $conn, string $action)
{
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairPost;
    $match_pram = getRequestPrams(POST, $actionPairPost[$action][MATCH]);
    $modID = mysqli_real_escape_string($conn, $match_pram['modID']);
    if (checkAuthorisation($conn, $modID, $id) === null) {
        setOut(401, ['for' => $match_pram], 'Unauthorised, Permission denied');
        return false;
    }
    $qry = buildQuery($conn, DELETE, DEVICES_TABLE, $match_pram, []);
    $stat = query_exec($conn, $qry);
    if ($stat === 1) {
        /*  */
        $qry = 'UPDATE ' . MODULES_TABLE . ' SET num_of_device = (SELECT COUNT(*) FROM ' . DEVICES_TABLE . ' WHERE module_id = ' . $modID . ') WHERE module_id = ' . $modID . ';';
        if (query_exec($conn, $qry) !== 1) {
            setOut(500, ['for' => $match_pram], 'Something went wrong');
            return false;
        }
        setOut(203, ['for' => $match_pram], 'Removed the device Successfully');
        return true;
    } else if ($stat === 100) {
        setOut(404);
        return false;
    }
    setOut(500);
    return false;
}
