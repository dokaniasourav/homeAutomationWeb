<?php

//echo  date("Y-m-d h:i:sa");

if(strlen(strtolower(filter_var(trim('       aa-F9-cc-B2-5E-0D    '), FILTER_VALIDATE_MAC))) === 17){
    echo "Invalid";
} else{
    var_dump(strtolower(filter_var('aa-F9dd-cc-B2-5E-0D', FILTER_VALIDATE_MAC)));
    echo "valid";
}


/*foreach(timezone_abbreviations_list() as $abbr => $timezone){
        foreach($timezone as $val){
                if(isset($val['timezone_id'])){
                        var_dump($abbr,$val['timezone_id']);
                        echo '<br>';
                }
        }
}*/