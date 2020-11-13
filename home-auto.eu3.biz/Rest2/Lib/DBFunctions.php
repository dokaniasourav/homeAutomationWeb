<?php

define("MYSQL_CONN_ERROR", "Unable to connect to database.");

function DBConnect(string $db = "greenovation_db"): mysqli
{
    mysqli_report(MYSQLI_REPORT_STRICT);
    $db_host = "localhost";
    $username = "2271320_database";
    $pwd = "159357.Skd";
    try {
        $conn = new mysqli($db_host, $username, $pwd, $db);
        //mysqli_options($conn,MYSQLI_OPT_INT_AND_FLOAT_NATIVE,true);
        return $conn;
    } catch (mysqli_sql_exception $e) {
        handleDBException($e);
        return null;
    }
}

function DBDisconnect(mysqli $link)
{
    try {
        mysqli_close($link);
    } catch (mysqli_sql_exception $e) {
        handleDBException($e);
    }
}

function buildQuery(mysqli $conn, int $queryType, string $tableName, array $matchPrams = [], array $setPrams = [])
{
    $conditionString = "";
    if ($matchPrams !== []) {                                                        // Build Condition Parameters
        $matchPrams_tbl = transformArray($matchPrams);
        $notFirst = false;
        foreach ($matchPrams_tbl as $key => $val) {
            if ($notFirst) {
                $conditionString .= " AND ";
            }
            $notFirst = true;
            $val = trim(mysqli_real_escape_string($conn, $val));
            $val = ($val === null || strlen($val) === 0) ? 'null' : "'" . $val . "'";
            $conditionString .= "`$key` = $val ";
        }
    }
    //echo "Cond: $conditionString";
    if ($queryType === UPDATE) {
        $pramString = "";
        if ($setPrams !== []) {
            $setPrams_tbl = transformArray($setPrams);
            foreach ($setPrams_tbl as $key => $val) {
                $val = trim(mysqli_real_escape_string($conn, $val));
                $val = ($val === null || strlen($val) === 0) ? 'null' : "'" . $val . "'";
                $pramString .= "`$key` = $val, ";
            }
        }
        $qry = "UPDATE `$tableName` SET " . rtrim($pramString, ', ');
    } else if ($queryType === INSERT || $queryType === SELECT) {
        $pramString = "";
        $valueString = "";
        if ($setPrams !== []) {
            $setPrams_tbl = transformArray($setPrams);
            foreach ($setPrams_tbl as $key => $val) {
                $val = trim(mysqli_real_escape_string($conn, $val));
                $val = empty($val) ? 'null' : "'$val'";
                $pramString .= "`$key`, ";
                $valueString .= "$val, ";
            }
            $pramString = rtrim($pramString, ", ");
            $valueString = rtrim($valueString, ", ");
        }
        if ($queryType === INSERT && !empty($pramString)) {
            $qry = "INSERT INTO `$tableName` ($pramString) VALUES ($valueString)";
        } else if ($queryType === SELECT) {
            $pramString = empty($pramString) ? '*' : $pramString;
            $qry = "SELECT $pramString FROM `$tableName`";
        } else {
            $qry = "";
        }
    } else if ($queryType === DELETE) {
        $qry = "DELETE FROM `$tableName` ";
    } else {
        Err("Error building query, Invalid TYPE");
        return null;
    }
    if (!empty($conditionString) && $queryType !== INSERT)
        return $qry . " WHERE " . $conditionString;
    return $qry;
}


function query_exec(mysqli $conn, string $qry)
{
    if (checkQryAndConnection($conn, $qry) === false) {
        return 0;
    }
    if (!($r = $conn->query($qry))) {
        Err('Error No: <b>' . mysqli_errno($conn) . '</b><br>Error Description ' . mysqli_error($conn));
        Err("Error executing query $qry");
        return mysqli_errno($conn);
    } else if (mysqli_affected_rows($conn) === 0) {
        // Err('No rows Were affected');
        // Err("0 rows affected by $qry");
        return 100;
    }
    Err("Executed :- $qry");
    return 1;
}

function getResultFromQuery(mysqli $conn, string $qry, int $n = NORMAL)
{
    if (checkQryAndConnection($conn, $qry) === false) {
        return null;
    }
    if ($n === NORMAL) {
        $result = $conn->query($qry);
        if (!$result) {
            Err('Error No: <b>' . mysqli_errno($conn) . '</b>');
            Err("Error executing Query " . $qry);
            Err(mysqli_error($conn));
        } else if ($result->num_rows == 0) {
            //Err("No Result for query " . $qry);
            $result->free();
        } else {
            $output = [];
            while ($row = $result->fetch_assoc()) {
                $output[] = $row;
            }
            $result->free();
            //Err("Got result from :- $qry");
            return $output;
        }
    } else if ($n === PROCEDURE) {
        $sqlSuccess = $conn->multi_query($qry);
        if ($sqlSuccess) {
            if ($conn->more_results()) {
                $result = $conn->use_result();                          // Get the first buffered result set, the one with our data.
                $output = array();                                      // Put the rows into the output array
                while ($row = $result->fetch_assoc()) {
                    $output[] = $row;
                }
                // Free the first result set, If you forget this one, you will get the "out of sync" error.
                $result->free();
                // Go through each remaining buffered result and free them as well.
                // This removes all extra result sets returned, clearing the way for the next SQL command.
                while ($conn->more_results() && $conn->next_result()) {
                    $extraResult = $conn->use_result();
                    if ($extraResult instanceof mysqli_result) {
                        $extraResult->free();
                    }
                }
                Err("Got procedure out :- $qry");
                return $output;
            } else {
                Err("Got procedure null :- $qry");
                return null;
            }
        } else {
            Err('Error No: <b>' . mysqli_errno($conn) . '</b>');
            Err("Error executing Query " . $qry);
            Err(mysqli_error($conn));
        }
    }
    return null;
}

function checkQryAndConnection(mysqli $conn, string $qry)
{
    if (!$conn) {
        Err('No connection to DB. Please check config');
        return false;
    }
    if (strlen($qry) === 0) {
        Err('No Qry string provided. Cannot execute');
        return false;
    }
}

function handleDBException(mysqli_sql_exception $e)
{
    Err($e->getMessage() . '<br>\n' . $e->getTraceAsString());
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    setOut(500);
    //die(json_encode($out));
}
