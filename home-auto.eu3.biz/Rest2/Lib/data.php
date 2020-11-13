<?php
$error = '';
$out = [
    'action' => 'Unset',
    'status' => 'Unset',
    'data'   => 'Unset'
];

//function setOutput(string $status,array $data){
//    global $out;
//    $out['status'] = $status;
//    $out['data']   = $data;
//}

//define('GENERAL_INFO'   ,100);
//define('ACTION_INFO'    ,101);
//define('PROCESSING'     ,102);
//define('OK'             ,200);
//define('CREATED'        ,201);
//define('ACCEPTED'       ,202);
//define('PROCESSED'      ,203);
//define('LOGIN_SUCCESS'  ,204);
//define('GEN_SUCCESS'    ,205);
//define('UPDATE'         ,300);
//define('INVALID_ACTION' ,400);
//define('UNAUTHORIZED'   ,401);
//define('INCOMP_PRAM'    ,402);
//define('INVALID_PRAM'   ,403);
//define('NOT_FOUND'      ,404);
//define('METHOD_NOT'     ,405);
//define('NOT_ACCEPTED'   ,406);
//define('REQUEST_TIME'   ,408);
//define('CONFLICT'       ,409);
//define('INVALID_KEY'    ,410);
//define('EMPTY_RESULT'   ,411);
//define('LOGIN_FAIL'     ,420);
//define('INTERNAL_ERROR' ,500);
//define('NOT_IMPL'       ,501);
//define('SERVICE_UNAVIL' ,503);

define('NORMAL', 1111);
define('PROCEDURE', 2222);

define('NORMAL_USER', 2);
define('MODULE_USER', 11);

define('GRANT_ALL_ACCESS', 2);

define('UPDATE', 1001);
define('INSERT', 1002);
define('SELECT', 1003);
define('DELETE', 1004);

define('POST', 111);
define('GET', 222);
define('PUT', 333);
define('DUMMY', 444);

define('AUTH', 0);
define('MATCH', 1);
define('SET_P', 2);
define('GET_P', 2);
define('OUT_P', 3);

define('EXPIRES', 60 * 30);                                 // 1 Min
define('EXPIRES_ALL', 3600);                             // 1 Hour

define('SESSION_TABLE', 'sessions_tbl');
define('LOGIN_TABLE', 'users_tbl');
define('USER_DETAILS_TABLE', 'users_info_tbl');
define('MODULES_TABLE', 'modules_tbl');
define('AUTHORISATION_TABLE', 'authorisation_tbl');
define('MODULES_MAC_ID', 'modules_mac_tbl');
define('DEVICES_TABLE', 'devices_tbl');

$loginTable             = 'users';
$detailsTable           = 'user_detail';
$moduleTable            = 'modules';
$authorisationTable     = 'users_modules';

/** @var Array $columnNames
 *  Stores relational information about
 *  query parameters and corresponding
 *  table name.
 */
$columnNames = [
    /* Device Table */
    'modID'     => 'module_id',
    'devID'     => 'device_id',
    'name'      => 'appellation',
    'devType'   => 'device_type',
    'state'     => 'device_state',
    'active'    => 'enabled',
    'desc'      => 'description',
    'dev_curr'  => 'device_current',
    'dev_phase' => 'device_angle',
    'up_by'     => 'updated_by',
    'up_at'     => 'last_update',

    /* Modules table */
    'uID'       => 'user_id',
    'numDev'    => 'num_of_device',
    'created'   => 'created',
    'conf'      => 'configuration',
    'pinMap'    => 'pin_map',
    'cred'      => 'credential',
    'firm'      => 'firmware',
    'lastOta'   => 'last_ota',
    'mod_curr'  => 'module_current',
    'mod_phase' => 'module_angle',
    'mod_power'     => 'total_power',

    /* Modules identity table */
    'mac'       => 'mac_address',
    'uniqueId'  => 'unique_id',

    /*Sessions table*/
    'key'       => 'session_key',
    'expire'    => 'issued',
    'uAg'       => 'userAgent',
    'type'      => 'type',


    /* Users details table */
    'fName'     => 'first_name',
    'mName'     => 'middle_name',
    'lName'     => 'last_name',
    'theme'     => 'theme',
    'pic'       => 'profile_pic',
    'about'     => 'about',
    'mob'       => 'mobile',
    'gender'    => 'gender',
    'address'   => 'address',

    /* Users table */
    'user'      => 'email',
    'email'     => 'email',
    'pass'      => 'password',

    /* Users modules table */
    'auth'      => 'authorisation'
];

$allowModuleActions = ['login', 'addModule'];
$disAllowUserActions = ['addModule', 'moduleLogin'];

$actionPairPost  = [
    /* Add and remove */
    'addModule'         => [['key', 'mac'],  [],            ['user', 'numDev'], ['modID']],
    'addDevice'         => [['key'],        ['modID'],      ['devID', 'name', 'devType'], []],
    'addUser'           => [[],             [],             ['user', 'pass'],         []],
    'removeModule'      => [['key'],    ['modID'],          [], []],
    'removeDevice'      => [['key'],    ['modID', 'devID'],  [], []],

    /* Set user parameters */
    'login'             => [['user', 'pass'], [], [], []],
    'signOut'           => [['key'], [], [], []],

    /* Set module parameters */
    'setModName'        => [['key'], ['modID'], ['name'], []],
    'setModDesc'        => [['key'], ['modID'], ['desc'], []],
    'setModConf'        => [['key'], ['modID'], ['conf'], []],
    'setModPins'        => [['key'], ['modID'], ['pinMap'], []],
    'setModCred'        => [['key'], ['modID'], ['cred'], []],
    'setModFirm'        => [['key'], ['modID'], ['firm'], []],
    'setModOta'         => [['key'], ['modID'], ['lastOta'], []],
    'setModCurr'        => [['key'], ['modID'], ['mod_curr', 'mod_phase'], []],
    'setModPower'       => [['key'], ['modID'], ['mod_power'], []],


    /* Set User parameters */
    'setUserFullName'   => [['key'], [], ['fName', 'mName', 'lName'], []],
    'setUserAdd'        => [['key'], [], ['address'], []],
    'setUserPic'        => [['key'], [], ['pic'], []],
    'setUserTheme'      => [['key'], [], ['theme']],
    'setUserMail'       => [['key'], [], ['mail'], []],
    'setUserMob'        => [['key'], [], ['mob'], []],
    'setUserBio'        => [['key'], [], ['about'], []],
    'setUserGender'     => [['key'], [], ['gender'], []],

    /* Set device parameters */
    'setDevActivation'  => [['key'], ['modID', 'devID'], ['active'],               []],
    'setDevName'        => [['key'], ['modID', 'devID'], ['name'],                 []],
    'setDevState'       => [['key'], ['modID', 'devID'], ['state', 'up_at'],       []],
    'setDevDetail'      => [['key'], ['modID', 'devID'], ['desc'],                 []],
    'setDevType'        => [['key'], ['modID', 'devID'], ['devType'],              []],
    'setDevCurr'        => [['key'], ['modID', 'devID'], ['dev_curr', 'dev_phase'], []]
];

$actionPairGet  = [

    /* All rounders */
    'getModAll'         => [['key'], [],        ['modID', 'name', 'numDev', 'desc']],
    'getModAllMe'       => [['key'], ['modID'], ['name',  'numDev', 'created', 'conf', 'pinMap', 'cred', 'firm', 'lastOta', 'mod_curr', 'mod_phase', 'mod_power', 'desc']],
    'getDevEverything'  => [['key'], ['modID'], ['devID', 'name', 'state', 'active', 'devType', 'dev_curr', 'dev_phase', 'desc']],
    'getDevAllActive'   => [['key'], ['modID'], ['devID', 'name', 'state', 'devType', 'dev_curr', 'dev_phase', 'desc']],
    'getUserAll'        => [['key'], [],        ['fName', 'mName', 'lName', 'address', 'pic', 'theme', 'gender', 'mob', 'about']],

    /* Get Module parameters */
    'getModName'        => [['key'], ['modID'], ['name']],
    'getModNumDev'      => [['key'], ['modID'], ['numDev']],
    'getModNumCreated'  => [['key'], ['modID'], ['created']],
    'getModDesc'        => [['key'], ['modID'], ['desc']],
    'getModConf'        => [['key'], ['modID'], ['conf']],
    'getModPinMap'      => [['key'], ['modID'], ['pinMap']],
    'getModCred'        => [['key'], ['modID'], ['cred']],
    'getModFirm'        => [['key'], ['modID'], ['firm']],
    'getModOta'         => [['key'], ['modID'], ['lastOta']],
    'getModCurr'        => [['key'], ['modID'], ['mod_curr', 'mod_phase']],
    'getModPower'       => [['key'], ['modID'], ['mod_power']],

    /* Get User parameters */
    'getUserName'       => [['key', 'pass', 'otp'], [], ['user']],
    'getUserFullName'   => [['key'], [], ['fName', 'mName', 'lName']],
    'getUserAdd'        => [['key'], [], ['address']],
    'getUserPic'        => [['key'], [], ['pic']],
    'getUserTheme'      => [['key'], [], ['theme']],
    'getUserMail'       => [['key'], [], ['mail']],
    'getUserMob'        => [['key'], [], ['mob']],
    'getUserBio'        => [['key'], [], ['about']],

    /* Get device parameters */
    'getDevStateAll'    => [['key'], ['modID'],          ['devID', 'state', 'up_at']],
    'getDevName'        => [['key'], ['modID', 'devID'], ['name']],
    'getDevState'       => [['key'], ['modID', 'devID'], ['state', 'up_at']],
    'getDevDetail'      => [['key'], ['modID', 'devID'], ['desc']],
    'getDevType'        => [['key'], ['modID', 'devID'], ['devType']],
    'getDevCurr'        => [['key'], ['modID', 'devID'], ['dev_curr', 'dev_phase']]
];

// $integerFields = [
//     'modID', 'numDev', 'devID', 'state', 'devType', 'type', 'theme', 'active', 'curr'
// ];

$rangeAllowed = [
    'modID'         => [0, 65536],
    'numDev'        => [0, 127],
    'devID'         => [0, 127],
    'state'         => [0, 1],
    'devType'       => [0, 127],
    'type'          => [0, 100],
    'theme'         => [0, 127],
    'active'        => [0, 1],
    'dev_curr'      => [-1000, 1000],
    'dev_phase'     => [-1000, 1000],
    'mod_curr'      => [-1000, 1000],
    'mod_phase'     => [-1000, 1000]
];

$actionPairDelete = [
    'User'                => [['key'], [], []],
    'Module'              => [['key'], ['modID'], []],
    'Device'              => [['key'], [''], []],
];

$statusCodes = [
    100 => 'Information',
    200 => 'Success',
    300 => 'Redirection',
    400 => 'Client Error',
    500 => 'Server Error'
];

$statusMessage = [
    100 => 'General Information',
    101 => 'Action Information',
    102 => 'Processing',

    200 => 'OK',            /* Get something */
    201 => 'Created',       /* Adding something to database */
    202 => 'Accepted',      /* Update something */
    203 => 'Processed',     /* Removed something */
    204 => 'Login Success',
    205 => 'Generation Success',

    300 => 'Update',        /* When token expires */

    400 => 'Invalid Action',    /*  */
    401 => 'Unauthorized',      /*  */
    402 => 'Incomplete Parameters', /*  */
    403 => 'Invalid Parameters',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Invalid Key',
    411 => 'Empty Result',
    420 => 'Login Failed',

    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    503 => 'Service Unavailable'
];

function setOut(int $status, array $output = [], string $msg = '')
{
    global $out, $statusCodes, $statusMessage;
    if (isset($statusMessage[$status])) {
        $out['status'] = [
            'code' => $status,
            'type' => $statusCodes[$status - $status % 100],
            'value' => $statusMessage[$status]
        ];
    }
    if ($output === null) {
        $output = [];
    }
    if (isset($output['for'])) {
        $out['data']['for'] = $output['for'];
    }
    if (isset($output['out'])) {
        $out['data']['out'] = $output['out'];
    }
    if (isset($output['get'])) {
        $out['data']['get'] = $output['get'];
    }
    if (isset($output['set'])) {
        $out['data']['set'] = $output['set'];
    }
    if (isset($output['err'])) {
        $out['data']['err'] = $output['err'];
    }
    if ($msg !== '') {
        $out['status']['Msg'] = $msg;
    }
    global $error;
    if ($error !== '') {
        $out['data']['errMsg'] = $error;
    }
    return true;
}
