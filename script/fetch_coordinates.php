<?php
  /* script requires: curl 
     Install: sudo apt-get install php5-curl 
  */

  $arDataInput = [];
  $arDataCache = [];

  $GLOBALS['constDataHeaders']['brin']        =0;
  $GLOBALS['constDataHeaders']['street']      =1;
  $GLOBALS['constDataHeaders']['number']      =2;
  $GLOBALS['constDataHeaders']['zip']         =3;
  $GLOBALS['constDataHeaders']['city']        =4;
  $GLOBALS['constDataHeaders']['state']       =5;
  $GLOBALS['constDataHeaders']['country']     =6;
  $GLOBALS['constDataHeaders']['country_code']=7;
  $GLOBALS['constDataHeaders']['lat']         =1;
  $GLOBALS['constDataHeaders']['lng']         =2;


  # Load text csv file into an array.
  function csv_to_array($filename='', $delimiter=';', $enclosure='"', $escape='\\') {
    if(!file_exists($filename) || !is_readable($filename))
      return FALSE;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 1000, $delimiter,$enclosure,$escape)) !== FALSE)
         $data[] = $row;
      fclose($handle);
    }
    return $data;
  }


  # save array to csv file.
  function array_to_csv($filename='', $data, $delimiter=';', $enclosure='"', $escape='\\') {
    if(!file_exists($filename) || !is_writable($filename))
      return FALSE;
    if (($handle = fopen($filename, 'w')) !== FALSE) {
      foreach ($data as $key => $value) {
        fputcsv ($handle, $value, $delimiter, $enclosure);
      }
      fclose($handle);
    }
  }


  # Check if the input exists in cache.
  function check_if_input_exists_in_cache($arInputItem, $arDataCache) {
    foreach ($arDataCache as $keyCache => $valueCache) {
      if ($valueCache[ $GLOBALS['constDataHeaders']['brin'] ]  == $arInputItem[ $GLOBALS['constDataHeaders']['brin'] ] ) 
      	  return true;
    }    
    return false;
  }


  # Call google and ask them to return geo coordinates based on zip, state, city & country.
  function fetch_coordinates($valueInputItem) {
    sleep(1); //to not upset google?
    $address  = urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['street'] ] );
    if ($valueInputItem[ $GLOBALS['constDataHeaders']['street'] ]!="NULL") $address .= ",+" . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['street'] ] );
    if ($valueInputItem[ $GLOBALS['constDataHeaders']['zip'] ]!="NULL") $address .= ",+" . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['zip'] ] );
    if ($valueInputItem[ $GLOBALS['constDataHeaders']['city'] ]!="NULL") $address .= ",+" . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['city'] ] );
    if ($valueInputItem[ $GLOBALS['constDataHeaders']['state'] ]!="NULL") $address .= ",+" . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['state'] ] );
    if ($valueInputItem[ $GLOBALS['constDataHeaders']['country'] ]!="NULL") $address .= ",+" . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['country'] ] );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $address . '&components=country:' . urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['country_code'] ])  . '&sensor=false');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $response_a = json_decode($response);
    switch ($response_a->status) {
      case "OK":
          $arCoordinates['lat']=$response_a->results[0]->geometry->location->lat;
          $arCoordinates['lng']=$response_a->results[0]->geometry->location->lng;
          return $arCoordinates;
          break;
      default: //If not OK then stop the program: "ZERO_RESULTS", "OVER_QUERY_LIMIT", "REQUEST_DENIED", "INVALID_REQUEST", "UNKNOWN_ERROR", "REQUEST_DENIED"
          echo $response_a->error_message . "\n" . "Program aborted...\n";
          return false; //stop the program.
     }
    return false; //stop the program.
  }


  # Crate a new dataCache item array. (Same as a inputDataItem but with geocoordinates, but without the count)
  function createDataCacheItem($valueIntput,$arCoordinates) {
    return array (
    $GLOBALS['constDataHeaders']['brin']    => $valueIntput[ $GLOBALS['constDataHeaders']['brin'] ],
    $GLOBALS['constDataHeaders']['lat']     => $arCoordinates['lat'],
    $GLOBALS['constDataHeaders']['lng']     => $arCoordinates['lng'] );
  }


  # Loop through Input Data Array, check if an item already has been cached (and geo fetched)
  function fetch_coordinates_loop($arDataInput, $arDataCache) {
    $arDataCache_additions = null;
    $arCoordinates = [];
    foreach ($arDataInput as $keyInput => $valueInput) {
      echo "**Processing: " . $valueInput[ $GLOBALS['constDataHeaders']['brin'] ];
      if (check_if_input_exists_in_cache($valueInput, $arDataCache)) {
        echo "[Cached]\n";
      } else {
        echo "[Not Cached]\n";
        echo "(Adding item to cache array)\n";
        $arCoordinates = fetch_coordinates($valueInput);
        if ($arCoordinates==false) break; //something happened. Stop the loop. Work is done for today...
        $arDataCache_additions[] = createDataCacheItem($valueInput, $arCoordinates);
        $count++;
      }
    }
    return $arDataCache_additions;
  }


  $arDataInput = csv_to_array("../public/data/input.csv");
  $arDataCache = csv_to_array("../public/data/cache.csv");
  $arDataCache_additions = fetch_coordinates_loop($arDataInput, $arDataCache);
  if (is_array($arDataCache_additions)) 
    array_to_csv("../public/data/cache.csv", array_merge($arDataCache, $arDataCache_additions) );

?>
