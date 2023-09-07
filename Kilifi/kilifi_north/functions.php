<?php
$g_user = "test_chv";
$g_password = "P@55w0rd";
$g_instance = "chis-training.health.go.ke"; //e.g chis-staging.health.go.ke

$instance_usernames = getAllUsernames();

function joinNameChars($name){
    $username = strtolower(str_replace("`", "",str_replace( "'", "", str_replace(" ", "_", str_replace(".", "", str_replace("  ", " ",str_replace(" ,", ",",properName($name))))))));

    return $username;
}

function nameToUsername($name){
    $pieces = explode("_", joinNameChars($name));

    $first = $pieces[0];
    $last = $pieces[array_key_last($pieces)];

    return substr($first, 0, 3) . $last;
}

function phoneAddPrefix($number, $prefix, $expectedDigits){
    $number = ltrim(ltrim($number, "0"), "+");
    if(substr($number, 0, 3) == $prefix && strlen($number) === $expectedDigits) return $number;
    
    $phone = $prefix.$number;

    if(strlen($phone) === $expectedDigits) return $phone;
 
}

function phoneRemovePrefix($number=701235402, $prefix, $expectedDigits){
    $number = ltrim(ltrim($number, "0"), "+");

    if(substr($number, 0, 3) != $prefix && strlen($number) === $expectedDigits) return $number;
    
    $phone = str_replace($prefix, "", $number);

    if(strlen($phone) === $expectedDigits) return $phone;
 
}

function sexConverter($sex){
    $sex = strtolower($sex);

    if($sex === "male" || $sex === "female") return $sex;
    if(strlen($sex) === 1)
    {
       if($sex === "m") $expandedSex = "male";
       if($sex === "f") $expandedSex = "female";
    }
    else
    {
        if(strpos($sex, "f") !== false)
        {
            $expandedSex = "female";
        }
        else
        {
            $expandedSex = "male";
        }
    }

return $expandedSex;
}

function nameAddSuffix($name, $convertoUsernameFormat = true, $suffix = false)
{
    if($convertoUsernameFormat) $username = joinNameChars($name);
    if($suffix) $username = $username . $suffix;

    return $username;
}

function properName($name){
    //replace multiple spaces with one space and capitalize first letter of each word
    return trim(ucwords(strtolower(str_replace("/", "-", preg_replace('!\s+!', ' ', $name))))); 
}

function searchDetailsInArray($array, $search)
{
    $found = false;

    foreach($array as $key => $value){
        foreach($search as $key_search=>$value_search)
        {
            if($value[$key_search] == $value_search)
            {
                $found = true;
            }
            else
            {
                $found = false;
            }
        }
    }

return $found;
}

function generateCHTPassword(){
    return "S3cr3t_" . str_pad(rand(1,999), 3, "0");
}

function searchInArray($array, $search)
{
    $found = false;

    foreach($array as $key => $value){
        if($value == $search)
        {
            $found = true;
            $newValue = modifySearch($array, $search);
        }
    }

return $found;
}

function modifySearch($array, $search){
    global $instance_usernames;
    
    $i=1;
    $newSearch = $search;
    if(in_array($newSearch, $array) || in_array($newSearch, $instance_usernames)){
        while(in_array($newSearch, $array) || in_array($newSearch, $instance_usernames))
        {
            $newSearch = $search . "_" . $i;
            $i++; 
        }
    }
return $newSearch;
}

function getUser($username)
{
		global $g_user;
		global $g_password;
		global $g_instance;

	$url = "https://" .$g_user.":" .$g_password. "@" .$g_instance. "/_users/org.couchdb.user%3A" . $username;

	$retry = 0;
	//  Initiate curl
	$ch = curl_init();
	//We want headers too
	// Disable SSL verification
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	// Will return the response, if false it print the response
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	curl_setopt($ch, CURLOPT_URL,$url);

	$result=curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if($httpcode == "301")
	{
		die("Add letter 's' to http i.e make it https");
	}
	while($httpcode >= 400 && $retry < 4){
		$result =curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$retry++;
	}

	// Closing
	curl_close($ch);
	return $result;

}

function getManyDocs($usernames)
{
		global $g_user;
		global $g_password;
		global $g_instance;
        $usernames = array_map(function($str){return "org.couchdb.user:" . $str;}, $usernames);
	$url = "https://" .$g_user.":" .$g_password. "@" .$g_instance. "/_users/_all_docs";  

		$jsonArray = array();
		$jsonArray['keys'] = $usernames;  
		$content = json_encode($jsonArray);
		$retry = 0;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

		$response = curl_exec($curl);

		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if($httpcode == "301")
			{
				die("Add letter 's' to http i.e make it https");
			}
		while($httpcode >= 400 && $retry < 4 && !$response){
				$response =curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$retry++;
			}


		curl_close($curl);
		return $response;
}

function getAllDocs()
{
		global $g_user;
		global $g_password;
		global $g_instance;
	$url = "https://" .$g_user.":" .$g_password. "@" .$g_instance. "/_users/_all_docs";  

		$retry = 0;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));

		$response = curl_exec($curl);

		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if($httpcode == "301")
			{
				die("Add letter 's' to http i.e make it https");
			}
		while($httpcode >= 400 && $retry < 4 && !$response){
				$response =curl_exec($curl);
				$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$retry++;
			}


		curl_close($curl);
		return $response;
}

function getAllUsernames(){
    $result = json_decode(getAllDocs(), true);
    $rows_results = $result['rows'];
    $usernames = array();

    foreach($rows_results as $key => $value)
    {
        if(strpos($value['id'], "_design/_auth") !== false) continue;
        $usernames[] = str_replace("org.couchdb.user:", "", $value['id']);
    }

    return $usernames;
}