<?php

function saveNewKey(mysqli $conn, int $id, string $key, int $type)
{
    /* Add constraints for adding multiple session keys on a single user */
    $userAgent  = mysqli_real_escape_string($conn, trim($_SERVER['HTTP_USER_AGENT']));
    $prams = [
        'uID'    => $id,
        'key'    => $key,
        'uAg'    => $userAgent,
        'type'   => $type
    ];
    $qry = buildQuery($conn, INSERT, SESSION_TABLE, [], $prams);
    $status = query_exec($conn, $qry);
    if ($status === 1) {
        return true;
    } else if ($status === 100) {
        Err('Error Inserting key for user');
    }
    return false;
}

function getToken()
{
    try {
        $length = random_int(32, 64);
    } catch (Exception $exception) {
        Err($exception->getMessage());
        $length = 63;
    }
    $token = "";
    $codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $codeAlphabet .= 'abcdefghijklmnopqrstuvwxyz';
    $codeAlphabet .= '0123456789';
    //$codeAlphabet.= '!@#$%^*()_-{}[]:;,.<>?~';
    $max = strlen($codeAlphabet); // edited

    for ($i = 0; $i < $length; $i++) {
        try {
            $token .= $codeAlphabet[random_int(0, $max - 1)];
        } catch (Exception $exception) {
            Err($exception->getMessage());
        }
    }
    return $token;
}

function login(mysqli $conn)
{
    $match_tbl = getRequestPrams(POST, ['user', 'pass']);
    /* Gets the requested parameters from the request object */
    $loginSuccess = false;
    $type = NORMAL_USER;
    $qry = buildQuery($conn, SELECT, LOGIN_TABLE, $match_tbl, []);
    $output = getResultFromQuery($conn, $qry);
    if ($output === null) {
        $mac_add = strtolower(trim($match_tbl['user']));                                        // Login Failed
        if (strlen(filter_var(trim($mac_add), FILTER_VALIDATE_MAC)) === 17) {
            $qry = buildQuery(
                $conn,
                SELECT,
                MODULES_MAC_ID,
                ['mac' => $mac_add, 'uniqueId' => $match_tbl['pass']],
                []
            );
            $output = getResultFromQuery($conn, $qry);
            if ($output !== null && extractIntPram($output[0], 'modID') > 0) {
                $type = MODULE_USER;
                $setPram = ['user' => $mac_add, 'pass' => $match_tbl['pass'], 'type' => $type];
                $qry = buildQuery($conn, INSERT, LOGIN_TABLE, [], $setPram);
                if (query_exec($conn, $qry) === 1) {
                    /* Success in adding mac records in db */
                    $qry = buildQuery($conn, SELECT, LOGIN_TABLE, $setPram);
                    $output = getResultFromQuery($conn, $qry);
                    if ($output !== null) {
                        $loginSuccess = true;
                    }
                }
            }
        }
    } else {
        $loginSuccess = true;
        $type    = extractIntPram($output[0], 'type');
    }
    if ($loginSuccess === false) {
        Err("Error logging in. The username or password did not match");
        setOut(420, ["for" => $match_tbl], "Invalid Username or Password");
        return $loginSuccess;
    }
    $key = getToken();
    $user_id = extractIntPram($output[0], 'uID');
    if (saveNewKey($conn, $user_id, $key, $type)) {
        setOut(204, ['out' => [
            'key' => $key,
            'expires' => date("D M d, Y G:i", time() + EXPIRES)
        ]]);
        return true;
    }
    setOut(500);
    return false;
}

function checkLogin(mysqli $conn)
{
    $userKey = $_POST['key'];
    global $columnNames;
    /* See if the userKey exists in the table or not */
    $qry = sprintf(
        'DELETE FROM `%s` WHERE (TIME_TO_SEC(TIMEDIFF(now(),`%s`.issued)) > %d)',
        SESSION_TABLE,
        SESSION_TABLE,
        EXPIRES_ALL
    );
    if (query_exec($conn, $qry) === 0) {
        setOut(500, [], 'Something went wrong');
    }

    $match_tbl = ['key' => $userKey, 'uAg' => $_SERVER['HTTP_USER_AGENT']];
    $qry = buildQuery($conn, SELECT, SESSION_TABLE, $match_tbl, []);
    unset($match_tbl['uAg']);
    $output = getResultFromQuery($conn, $qry);
    if ($output === null) {
        setOut(410, ['for' => $match_tbl], 'Please login again');
        return null;
    }
    /* We got something from the sessions table now */
    if (extractIntPram($output[0], 'uID') < 0) {
        setOut(500, ['for' => $match_tbl], 'Something is not right');
        return null;
    }
    $id   = extractIntPram($output[0], 'uID');
    $type = extractIntPram($output[0], 'type');
    $userKey = trim(mysqli_real_escape_string($conn, $userKey));

    /* Now we DELETE the expired session keys from table */
    /* If requested key is more then EXPIRES sec old, it is deleted.
            also, all keys in table which are more than EXPIRES_ALL secs old are also deleted */
    $qry2 = sprintf(
        'DELETE FROM `%s` WHERE (TIME_TO_SEC(TIMEDIFF(now(), `%s`.issued)) > %d AND `%s`.%s= \'%s\')',
        SESSION_TABLE,
        SESSION_TABLE,
        EXPIRES,
        SESSION_TABLE,
        $columnNames['key'],
        $userKey
    );
    /*$qry2 = 'DELETE FROM `'.SESSION_TABLE.'` WHERE (TIME_TO_SEC(TIMEDIFF(now(), `'.SESSION_TABLE.'`.issued)) > '.EXPIRES.' AND `'
        .$columnNames['key'].'`=\''.$userKey.'\') OR (TIME_TO_SEC(TIMEDIFF(now(), `'.SESSION_TABLE.'`.issued)) > '.EXPIRES_ALL.')'; */
    query_exec($conn, $qry2);
    /* CHECK AGAIN FOR THE SAME KEY */
    $output = getResultFromQuery($conn, $qry);
    if ($output === null) {
        $newKey = getToken();
        if (saveNewKey($conn, $id, $newKey, $type)) {
            setOut(
                300,
                ['out' => [
                    "key" => $newKey,
                    'expires' => date("D M d, Y G:i", time() + EXPIRES)
                ]],
                "Token is no longer valid, Use this new key"
            );
        } else {
            setOut(500);
        }
        return null;
    }
    return [
        'id'   => $id,
        'type' => $type
    ];
}

function signOut(mysqli $conn)
{
    $prams = getRequestPrams(POST, ['key']);
    $qry = buildQuery($conn, DELETE, SESSION_TABLE, $prams, []);
    $res = query_exec($conn, $qry);
    if ($res === 1) {
        setOut(203);
    } else if ($res === 100) {
        setOut(410);
    } else if ($res === 0) {
        setOut(500);
    }
}

function addUser(mysqli $conn)
{
    $prams_tbl = getRequestPrams(POST, ['user', 'pass']);
    $qry = buildQuery($conn, INSERT, LOGIN_TABLE, [], $prams_tbl);
    $res = query_exec($conn, $qry);
    if ($res === 1) {
        setOut(201, ['out' => $prams_tbl], 'User Created Successfully');
    } else if ($res === 100) {
        setOut(409);
    } else {
        setOut(501);
    }
}

function getUser(mysqli $conn, string $action)
{
    $login = checkLogin($conn);
    if ($login === null) {
        return false;
    }
    $id = $login['id'];

    global $actionPairGet, $columnNames;
    $keys_get = $actionPairGet[$action][GET_P];                            // Get action pair to get
    $get_pram = getRequestPrams(DUMMY, $keys_get);                         // Get
    $keys_match = $actionPairGet[$action][MATCH];                          // Get action pair to match
    $match_pram = getRequestPrams(GET, $keys_match);
    $qry = buildQuery($conn, SELECT, USER_DETAILS_TABLE, ['uID' => $id], $get_pram);
    $out_value = getResultFromQuery($conn, $qry);

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
            404,
            [
                'get' => array_keys($get_pram),
                'for' => $match_pram
            ],
            'The user detail may be non existential'
        );
    }
    return false;
}

function moduleGenLogin(mysqli $conn, string $action)
{
    global $columnNames, $actionPairPost;
    $prams = getRequestPrams(POST, $actionPairPost[$action][0]);
    $match = ['key' => $prams['key']];
    $qry = buildQuery($conn, SELECT, SESSION_TABLE, $match, []);
    $output = getResultFromQuery($conn, $qry);
    if ($output === null) {
        setOut(410, ['for' => $match], 'Invalid Key obtained. Try again');
        return null;
    }
    if (extractIntPram($output[0], 'uID') < 0) {
        setOut(500, ['for' => $match], 'Something is not right');
        return null;
    }
    //$id = extractIntPram($output[0],'uID');

    /* GOT THE ID, now looking for the MAC in MODULES_MAC_ID table */
    $match2 = ['mac' => $prams['mac']];
    $qry = buildQuery($conn, SELECT, MODULES_MAC_ID, $match2, []);
    $output = getResultFromQuery($conn, $qry);

    if ($output !== null /*&& is_numeric($output[0][$columnNames['modID']])*/) {
        $newKey = getToken();
        $qry = buildQuery($conn, UPDATE, MODULES_MAC_ID, $match2, ['uniqueId' => $newKey]);
        if (query_exec($conn, $qry) === 1) {
            setOut(205, ['out' => ['key' => $newKey,  'mac' => $output[0][$columnNames['mac']]]], "Please use this key as your password");
        } else {
            setOut(500);
        }
        return null;
    }
    return true;
}
