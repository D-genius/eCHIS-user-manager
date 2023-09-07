<?php
$time_start = microtime(true);

use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\Statement;

require_once "../../includes/csv-master/autoload.php";

require_once "config.php";

require_once "functions.php";

if(!file_exists($csv_folder))
{
    mkdir($csv_folder, 0777, true);
}

$csv = Reader::createFromPath($details_file);
$csv->setHeaderOffset(0); //set the CSV header offset

//get 25 records starting from the 11th row
$stmt = Statement::create()
    // ->offset(0)
    // ->limit(3)
;

$records = $stmt->process($csv);

$person_health_unit_array_values = $cha_unique_details = $subcounty_unique_details = $subcounty_details = array();
$person_chv_reference_ids = $person_cha_reference_ids = $person_cha_reference_id =  
    $chu_reference_ids = $person_schmt_reference_ids = $subcounty_reference_ids = array();

$firstRun = 0;
foreach ($records as $record) {
    //create person.chv_area.csv
    $person_chv_reference_ids[] = $reference_chv_id = modifySearch($person_chv_reference_ids, joinNameChars($record[$chv_name]));
    $reference_chv_area_id = $reference_chv_id. "_area";

    if($firstRun == 0){
    $person_cha_reference_id[] = $reference_cha_id = modifySearch($person_cha_reference_id, joinNameChars($record[$cha_name]));
    $chu_reference_ids[] = $reference_chu_id = modifySearch($chu_reference_ids, joinNameChars($record[$chu_name]));
    $person_schmt_reference_ids[] = $reference_schmt_id = modifySearch($person_schmt_reference_ids, joinNameChars($record[$subcounty_contact_name]));
    $subcounty_reference_ids[] = $reference_subcounty_id = modifySearch($subcounty_reference_ids, joinNameChars($record[$subcounty_name]));
    }


    $person_chv_area_array_values[] = array(
        "reference_id:excluded" => $reference_chv_id,	
        "name" => properName($record[$chv_name]),	
        "plain_name:excluded" => properName($record[$chv_name]),	
        "phone" => phoneAddPrefix($record[$chv_phone], "254", 12),	
        "sex" => sexConverter($record[$chv_sex]),	
        "type" => 'contact',	
        "contact_type" => 'person',	
        "parent:contact WHERE reference_id=COL_VAL" => $reference_chv_area_id,	
        "reported_date:timestamp" => date('M, d Y'),	
        "chv_phone:excluded" => phoneRemovePrefix($record[$chv_phone], "254", 9),	
        "chv_sex:excluded" => sexConverter($record[$chv_sex])
    );
    

    $place_chv_area_array_values[] = array(
        "reference_id:excluded" => $reference_chv_area_id,	
        "plain_name:excluded" => properName($record[$chv_name]),
        "name" => properName($record[$chv_name]) . " Area",		
        "contact:GET _id OF contact WHERE reference_id=COL_VAL" => $reference_chv_id,	
        "type" => "contact",	
        "contact_type" => "d_community_health_volunteer_area",	
        "parent:contact WHERE reference_id=COL_VAL" => $reference_chu_id,	
        "reported_date:timestamp" => date('M, d Y'),	
        "link_facility_code" => trim($record[$link_facility_code]),	
        "link_facility_name" => trim(properName($record[$link_facility_name])),	
        "Chu_Name:excluded" => properName($record[$chu_name]),	
        "chu_code" => trim($record[$chu_code]),	
        "chu_name" => properName($record[$chu_name]) . " Community Health Unit"
    );
    
    $chv_password = generateCHTPassword();
    $users_array_values[] = array(
        "name" => properName($record[$chv_name]),	
        "username" => $reference_chv_id,	
        "password" => $chv_password,	
        "roles" => "community_health_volunteer",	
        "contact:contact WHERE reference_id=COL_VAL" => $reference_chv_id,	
        "place:GET _id OF contact WHERE reference_id=COL_VAL" => $reference_chv_area_id,	
        "phone" => phoneAddPrefix($record[$chv_phone], "254", 12)
    );

    $users_share_list_array_values[] = array(
        "name" => properName($record[$chv_name]),	
        "username" => $reference_chv_id,	
        "password" => $chv_password,	
        "roles" => "community_health_volunteer",	
        "area" => properName($record[$chv_name]) . " Area",	
        "CHU" => $record[$chu_name],	
        "phone" => phoneAddPrefix($record[$chv_phone], "254", 12)
    );
    
    $cha_details = array(
        "cha_name" => $record[$cha_name],
        "chu_name" => $record[$chu_name],
        "chu_code" => $record[$chu_code]
    );

    if(!searchDetailsInArray($cha_unique_details, $cha_details)){
        $cha_unique_details[] = $cha_details;

        if($firstRun != 0){

            $person_cha_reference_id[] = $reference_cha_id = modifySearch($person_cha_reference_id, joinNameChars($record[$cha_name]));
            $chu_reference_ids[] = $reference_chu_id = modifySearch($chu_reference_ids, joinNameChars($record[$chu_name]));
        }
        $person_health_unit_array_values[] = array(
            "reference_id:excluded" => $reference_chu_id . "_cha",	
            "plain_name:excluded" => properName($record[$cha_name]),	
            "name" => properName($record[$cha_name]),	
            "phone" => phoneAddPrefix($record[$cha_phone], "254", 12),		
            "type" => 'contact',	
            "contact_type" => 'person',	
            "parent:contact WHERE reference_id=COL_VAL" => $reference_chu_id,
            "reported_date:timestamp" => date('M, d Y'),	
            "cha_phone:excluded" => phoneRemovePrefix($record[$cha_phone], "254", 9),	
            "chu_name:excluded" => properName($record[$chu_name])
        );

        $place_health_unit_array_values[] = array(
            "reference_id:excluded" => $reference_chu_id,	
            "name" => properName($record[$chu_name]) . " Community Health Unit",	
            "code" => trim($record[$chu_code]),	
            "contact:GET _id OF contact WHERE reference_id=COL_VAL" => $reference_chu_id. "_cha",	
            "type" => "contact",	
            "contact_type" => "c_community_health_unit",	
            "reported_date:timestamp" => date('M, d Y'),	
            "chu_name:excluded" => properName($record[$chu_name]),	
            "parent:contact WHERE reference_id=COL_VAL" => $reference_subcounty_id . "_subcounty"
        );
        
        $cha_password = generateCHTPassword();
        $users_array_values[] = array(
            "name" => properName($record[$cha_name]),	
            "username" => $reference_cha_id,	
            "password" => $cha_password,	
            "roles" => "community_health_assistant",	
            "contact:contact WHERE reference_id=COL_VAL" => $reference_chu_id. "_cha",	
            "place:GET _id OF contact WHERE reference_id=COL_VAL" => $reference_chu_id,	
            "phone" => phoneAddPrefix($record[$cha_phone], "254", 12)
        );

        $users_share_list_array_values[] = array(
            "name" => properName($record[$cha_name]),	
            "username" => $reference_cha_id,	
            "password" => $cha_password,	
            "roles" => "community_health_assistant",	
            "area" => properName($record[$chu_name]) . " Community Health Unit",	
            "CHU" => $record[$chu_name],	
            "phone" => phoneAddPrefix($record[$cha_phone], "254", 12)
        );
    }

    $subcounty_details = array(
        "subcounty_contact_name" => $record[$subcounty_contact_name],
        "subcounty_name" => $record[$subcounty_name],
        "subcounty_code" => $record[$subcounty_code]
    );

    if(!searchDetailsInArray($subcounty_unique_details, $subcounty_details)){
        $subcounty_unique_details[] = $subcounty_details;

        if($firstRun != 0){

          $person_schmt_reference_ids[] = $reference_schmt_id = modifySearch($person_schmt_reference_ids, joinNameChars($record[$subcounty_contact_name]));
          $subcounty_reference_ids[] = $reference_subcounty_id = modifySearch($subcounty_reference_ids, joinNameChars($record[$subcounty_name]));
        }
        $person_sub_county_array_values[] = array(
            "reference_id" => $reference_subcounty_id . "_subcounty_person",	
            "name" => properName($record[$subcounty_contact_name]),	
            "phone" => phoneAddPrefix($record[$subcounty_contact_phone], "254", 12),	
            "type" => "contact",	
            "contact_type" => "person",	
            "reported_date:timestamp" => date('M, d Y'),	
            "parent:contact WHERE reference_id=COL_VAL" => $reference_subcounty_id . "_subcounty"

        );

        $place_sub_county_array_values[] = array(
            "reference_id:excluded" => $reference_subcounty_id . "_subcounty",	
            "name" => properName($record[$subcounty_name]),	
            "code" => trim($record[$subcounty_code]),	
            "contact:contact WHERE reference_id=COL_VAL" => $reference_subcounty_id . "_subcounty_person",	
            "type" => "contact",	
            "contact_type" => "b_sub_county",	
            "parent._id" => $county_uuid,	
            "reported_date:timestamp" => date('M, d Y')
        );
        
        $schmt_password = generateCHTPassword();
        $users_array_values[] = array(
            "name" => properName($record[$subcounty_contact_name]),	
            "username" => $reference_schmt_id,	
            "password" => $schmt_password,	
            "roles" => "sub_county_supervisor",	
            "contact:contact WHERE reference_id=COL_VAL" => $reference_subcounty_id . "_subcounty_person",	
            "place:GET _id OF contact WHERE reference_id=COL_VAL" => $reference_subcounty_id . "_subcounty",	
            "phone" => phoneAddPrefix($record[$subcounty_contact_phone], "254", 12)
        );

        $users_share_list_array_values[] = array(
            "name" => properName($record[$subcounty_contact_name]),	
            "username" => $reference_schmt_id,	
            "password" => $schmt_password,	
            "roles" => "sub_county_supervisor",	
            "area" => "Not Applicable",	
            "CHU" => properName($record[$subcounty_name]) . " Sub County",	
            "phone" => phoneAddPrefix($record[$subcounty_contact_phone], "254", 12)
        );
    }
$firstRun++;
}

$file_name_array = array(
    "csv/person.chv_area.csv" => array(
        "data" => $person_chv_area_array_values
    ),
    "csv/place.chv_area.csv" => array(
        "data" => $place_chv_area_array_values
    ),
    "csv/person.health_unit.csv" => array(
        "data" => $person_health_unit_array_values
    ),
    "csv/place.health_unit.csv" => array(
        "data" => $place_health_unit_array_values
    ),
    "csv/person.sub_county.csv" => array(
        "data" => $person_sub_county_array_values
    ),
    "csv/place.sub_county.csv" => array(
        "data" => $place_sub_county_array_values
    ),
    "csv/users.csv" => array(
        "data" => $users_array_values
    ),
    "./" . properName($record[$subcounty_name]) . "_users_share_list.csv" => array(
        "data" => $users_share_list_array_values
    ),
);
    //Insert files
    foreach($file_name_array as $key=>$value){
        $csvWrite = Writer::createFromPath($key, 'w+');
        $csvWrite->insertOne(array_keys($value['data'][0]));
        $csvWrite->insertAll($value['data']);
        // $csvWrite->output($path);
    }

$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes, otherwise seconds
$execution_time = ($time_end - $time_start);

//execution time of the script
echo '<b>Total Execution Time:</b> '.$execution_time.' Seconds';