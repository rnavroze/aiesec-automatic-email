<?php
// Function to sort out error reporting
function set_error_reporting()
{
    if (ENV == "dev")
        error_reporting(E_ALL);
    else
        error_reporting(0);
}

// Function to run query and return result
function run_query($sql)
{
    global $db;

    if (!$result = $db->query($sql))
    {
        if (ENV == "dev")
            die('There was an error running the query [' . $db->error . ']<br>' . $sql);
        else
        {
            $key = substr(md5(microtime()), 0, 6);
            error_log(microtime() . " [$key] " . 'There was an error running the query [' . $db->error . '] | Query: ' . $sql);
            die("An irrecoverable error occurred. Please contact the developers and include the error code: $key.");
        }   
    }
    
    return $result;
}

// Function to build an INSERT query
// Arguments //
// $insert_array: key-value pair of insert data
// $table_name
function insert_query($insert_array, $table_name)
{
    // Sanitize ALL THE STRINGS
    array_walk($insert_array, "sanitize");

    // Now prepare our query
    $sql = "INSERT INTO $table_name (";
    $sql .= implode(", ", array_keys($insert_array));
    $sql .= ") VALUES(";
    foreach ($insert_array as $value)
    {
        if ($value == 'NULL')
            $sql .= "NULL, ";
        else
            $sql .= "'$value', ";
    }
    $sql = rtrim($sql, ", ");
    // $sql .= implode("', '", $insert_array);
    $sql .= ");";

    // Run it
    return run_query($sql);
}

function update_query($insert_array, $table_name, $cond)
{
    // Sanitize ALL THE STRINGS
    array_walk($insert_array, "sanitize");

    // Now prepare our query
    $sql = "UPDATE $table_name SET ";
    foreach ($insert_array as $key => $value)
    {
        $sql .= "$key = ";
        if ($value == 'NULL')
            $sql .= "NULL, ";
        else
            $sql .= "'$value', ";
    }
    $sql = rtrim($sql, ", ");
    $sql .= " WHERE $cond;";

    // Run it
    return run_query($sql);
}

// Takes an element of an array and returns a new array with the key as that element
function array_select_key($array, $key)
{
    $ret_arr = [];
    foreach ($array as $value)
        $ret_arr[$value[$key]] = $value;
    return $ret_arr;
}

// Subvalue sort
function subval_sort($a, $subkey) 
{
    foreach($a as $key => $val)
        $b[$key] = strtolower($val[$subkey]);
    
    arsort($b);
    foreach ($b as $key => $val)
        $c[$key] = $a[$key];

    return $c;
}

function fetch_result($result)
{
    $out = [];
    for ($i = 0; $out[$i] = $result->fetch_assoc(); $i++);
    unset($out[$i]);
    return $out;
}

function fetch_one($result)
{
    $out = fetch_result($result);
    return isset($out[0]) ? $out[0] : false;
}

// Sanitize (used in array_walk)
function sanitize(&$string)
{
    global $db;
    $string = $db->escape_string($string);
}

// Date Range Generator
function date_range($first, $last, $step = '+1 day', $format = 'Y-m-d') 
{
    $dates = array();
    $current = strtotime( $first );
    $last = strtotime( $last );

    while( $current <= $last ) {

        $dates[] = date( $format, $current );
        $current = strtotime( $step, $current );
    }

    return $dates;
}

// This is not the best way to do this, but it works.
function encrypt_decrypt($string, $action = 0) 
{
    $output = false;

    $encrypt_method = "AES-256-CBC";
    require "secrets.php";

    // hash
    $key = hash('sha256', $secret_key);
    
    // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 0) 
    {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    }
    else if ($action == 1)
    {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }

    return $output;
}

// Debug stuff
function verbose_show($text)
{
    if (defined('VERBOSE') && VERBOSE == 1)
        echo $text;
}

// Convert HSV to RGB
// https://gist.github.com/Jadzia626/2323023
function fGetRGB($iH, $iS, $iV) 
{
    if($iH < 0)   $iH = 0;   // Hue:
    if($iH > 360) $iH = 360; //   0-360
    if($iS < 0)   $iS = 0;   // Saturation:
    if($iS > 100) $iS = 100; //   0-100
    if($iV < 0)   $iV = 0;   // Lightness:
    if($iV > 100) $iV = 100; //   0-100
    $dS = $iS/100.0; // Saturation: 0.0-1.0
    $dV = $iV/100.0; // Lightness:  0.0-1.0
    $dC = $dV*$dS;   // Chroma:     0.0-1.0
    $dH = $iH/60.0;  // H-Prime:    0.0-6.0
    $dT = $dH;       // Temp variable
    while($dT >= 2.0) $dT -= 2.0; // php modulus does not work with float
    $dX = $dC*(1-abs($dT-1));     // as used in the Wikipedia link
    switch(floor($dH)) {
        case 0:
            $dR = $dC; $dG = $dX; $dB = 0.0; break;
        case 1:
            $dR = $dX; $dG = $dC; $dB = 0.0; break;
        case 2:
            $dR = 0.0; $dG = $dC; $dB = $dX; break;
        case 3:
            $dR = 0.0; $dG = $dX; $dB = $dC; break;
        case 4:
            $dR = $dX; $dG = 0.0; $dB = $dC; break;
        case 5:
            $dR = $dC; $dG = 0.0; $dB = $dX; break;
        default:
            $dR = 0.0; $dG = 0.0; $dB = 0.0; break;
    }
    $dM  = $dV - $dC;
    $dR += $dM; $dG += $dM; $dB += $dM;
    $dR *= 255; $dG *= 255; $dB *= 255;
    // return round($dR).",".round($dG).",".round($dB);
    // Return as hex code instead
    $color = dechex( ($dR << 16) + ($dG << 8) + $dB );
    return str_repeat('0', 6 - strlen($color)) . $color;
}