<?php

function getModule(mysqli $conn, string $action)
{
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairGet, $columnNames;
    $keys_get = $actionPairGet[$action][GET_P];                            // Get action pair to get
    $get_pram = getRequestPrams(DUMMY, $keys_get);                  // Get
    $keys_match = $actionPairGet[$action][MATCH];                          // Get action pair to match
    $match_pram = getRequestPrams(GET, $keys_match);
    $out_value = [];

    $match_pram['uID'] = $id;
    $qry = buildQuery($conn, SELECT, AUTHORISATION_TABLE, $match_pram, []);
    unset($match_pram['uID']);
    $output = getResultFromQuery($conn, $qry);        // Gets all mod IDs from the AUTHORISATION database
    if ($output !== null && count($output) !== 0) {
        foreach ($output as $out) {
            $modID = $out[$columnNames['modID']];
            $qry = buildQuery(
                $conn,
                SELECT,
                MODULES_TABLE,
                ['modID' => $modID],
                $get_pram
            );
            $out_value[] = getResultFromQuery($conn, $qry)[0];
        }
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
    } else {
        setOut(
            401,
            [
                'get' => array_keys($get_pram),
                'for' => $match_pram
            ],
            'Unauthorised or Non-existent Value'
        );
    }
    return false;
}

function addModule(mysqli $conn, string $action)
{
    global $actionPairPost;
    $keys_set   = $actionPairPost[$action][SET_P];
    $set_pram   = getRequestPrams(POST, $keys_set);

    /* Checking if the number of devices field is proper */
    $numDev = $set_pram['numDev'];
    if ($numDev > 15 || $numDev < 1) {
        setOut(403, ['set' => $set_pram, 'for' => []], "Invalid Number of Devices");
        return false;
    }

    $mac_add = strtolower(trim($_POST['mac']));                                        // Extract mac
    if (strlen(filter_var($mac_add, FILTER_VALIDATE_MAC)) !== 17) {
        setOut(403, ['set' => $set_pram, 'for' => []], "Invalid MAC address");
        return false;
    }

    /* Checking the login of user */
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }

    if ($login['type'] !== MODULE_USER) {
        setOut(401, ['set' => $set_pram, 'for' => []], "Only modules are not allowed to do this");
        return false;
    }

    /* Check to see if Module exists in the Mac Table */
    $qry = buildQuery($conn, SELECT, MODULES_MAC_ID, ['mac' => $mac_add], []);
    $out = getResultFromQuery($conn, $qry);
    if ($out === null || extractIntPram($out[0], 'modID') < 0) {
        setOut(403, ['set' => $set_pram, 'for' => []], "Mac Address does not exist");
        return false;
    }
    $modID = extractIntPram($out[0], 'modID');

    /* Check new user record in the users table */
    $qry = buildQuery($conn, SELECT, LOGIN_TABLE, ['user' => $set_pram['user']]);
    $out = getResultFromQuery($conn, $qry);
    if ($out === null || extractIntPram($out[0], 'uID') < 0) {
        setOut(403, ['set' => $set_pram, 'for' => []], "User is not registered");
        return false;
    }
    $uID = extractIntPram($out[0], 'uID');

    /* Now insert the new module in the table */
    $set_pram['modID'] = $modID;
    $set_pram['name'] = 'Module ' . date('yymdhis');
    unset($set_pram['user']);
    $qry = buildQuery($conn, INSERT, MODULES_TABLE, [], $set_pram);

    $status = query_exec($conn, $qry);
    /* Module creation has failed, we need to send a failed message output */
    if ($status !== 1) {
        if (mysqli_errno($conn) == 1062) {
            setOut(409, ['set' => $set_pram, 'for' => []], 'Module Already Exists');
            return false;
        }
        setOut(500, ['set' => $set_pram, 'for' => []], "Cannot Create this Module");
        return false;
    }
    /* Insert the Authorisation record for User if the module insertion was a success*/
    $qry = buildQuery(
        $conn,
        INSERT,
        AUTHORISATION_TABLE,
        [],
        ['uID' => $uID, 'modID' => $modID, 'auth' => GRANT_ALL_ACCESS]
    );
    $qry = sprintf('%s, (\'%d\', \'%d\', \'%d\')', $qry, $login['id'], $modID, GRANT_ALL_ACCESS);
    /*Get the Module ID from MAC TABLE; Insert Module into Module_Table with the Set Prams */
    if (query_exec($conn, $qry) !== 1) {
        setOut(500, ['set' => $set_pram, 'for' => []], "Something went wrong");
        /* Since Auth was unsuccessful we now need to delete the inserted module */
        $qry = buildQuery($conn, DELETE, MODULES_TABLE, ['modID' => $modID], []);
        query_exec($conn, $qry);
        setOut(500, ['set' => $set_pram, 'for' => []], "Something Went wrong");
        return false;
    }
    /* Modules Authorisation were inserted successfully */
    setOut(201, ['set' => $set_pram, 'for' => [], 'out' => ['modID' => $modID]], "Module was Created Successfully");
    /* The devices will automatically be added to the table using TRIGGER*/
    return true;
}

function removeModule(mysqli $conn, string $action)
{
    /* Check user authentication */
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    /* Now check the module authorisation */
    global $actionPairPost;
    $match_pram = getRequestPrams(POST, $actionPairPost[$action][MATCH]);
    $modID = mysqli_real_escape_string($conn, $match_pram['modID']);
    if (checkAuthorisation($conn, $modID, $id) !== null) {
        $qry = buildQuery(
            $conn,
            UPDATE,
            AUTHORISATION_TABLE,
            ['mID' => $modID, 'uID' => $id],
            ['auth' => 0]
        );
        if (query_exec($conn, $qry) === 1) {
            setOut(203, ['for' => $match_pram], 'Module has been successfully removed');
            return true;
        }
        setOut(500, ['for' => $match_pram], 'Something went wrong');
        return false;
    }
    setOut(401, ['for' => $match_pram], 'Permission was denied');
    return false;
}

function checkAuthorisation(mysqli $conn, int $moduleID = -1, int $userID = -1)
{
    $qry = buildQuery(
        $conn,
        SELECT,
        AUTHORISATION_TABLE,
        ['uID' => $userID, 'modID' => $moduleID],
        []
    );
    $output = getResultFromQuery($conn, $qry);
    if ($output === null || extractIntPram($output[0], 'auth') === 0) {
        Err("Invalid moduleID or User has no permission");
        return null;
    }
    return $output;
}
