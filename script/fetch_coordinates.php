<?php
  /* script requires: curl 
     Install: sudo apt-get install php5-curl 
  */

  $arDataInput = [];
  $arDataCache = [];

  $GLOBALS['constDataHeaders']['date']   =0;
  $GLOBALS['constDataHeaders']['city']   =1;
  $GLOBALS['constDataHeaders']['zip']    =2;
  $GLOBALS['constDataHeaders']['state']  =3;
  $GLOBALS['constDataHeaders']['country']=4;
  $GLOBALS['constDataHeaders']['lat']    =5;
  $GLOBALS['constDataHeaders']['lng']    =6;


  # Load text csv file into an array.
  function csv_to_array($filename='', $delimiter=',', $enclosure='"', $escape='\\') {
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
  function array_to_csv($filename='', $data, $delimiter=',', $enclosure='"', $escape='\\') {
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
      if ($valueCache[ $GLOBALS['constDataHeaders']['date'] ]    == $arInputItem[ $GLOBALS['constDataHeaders']['date'] ]
       && $valueCache[ $GLOBALS['constDataHeaders']['city'] ]    == $arInputItem[ $GLOBALS['constDataHeaders']['city'] ]
       && $valueCache[ $GLOBALS['constDataHeaders']['zip'] ]     == $arInputItem[ $GLOBALS['constDataHeaders']['zip'] ]
       && $valueCache[ $GLOBALS['constDataHeaders']['state'] ]   == $arInputItem[ $GLOBALS['constDataHeaders']['state'] ]
       && $valueCache[ $GLOBALS['constDataHeaders']['country'] ] == $arInputItem[ $GLOBALS['constDataHeaders']['country'] ] 
       ) return true;
    }    
    return false;
  }


  # Call google and ask them to return geo coordinates based on zip, state, city & country.
  function fetch_coordinates($valueInputItem) {
    sleep(1); //to not upset google?
    $address =
      urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['city'] ] ) . ",+" .
      urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['state'] ] ) . ",+" .
      urlencode( $valueInputItem[ $GLOBALS['constDataHeaders']['country'] ] );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?address='.$address.',+CA&sensor=false');
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
    print_r($response_a);
    return false; //stop the program.
  }


  # Crate a new dataCache item array. (Same as a inputDataItem but with geocoordinates, but without the count)
  function createDataCacheItem($valueIntput,$arCoordinates) {
    return array (
    $GLOBALS['constDataHeaders']['date']    => $valueIntput[ $GLOBALS['constDataHeaders']['date'] ],
    $GLOBALS['constDataHeaders']['city']    => $valueIntput[ $GLOBALS['constDataHeaders']['city'] ],
    $GLOBALS['constDataHeaders']['zip']     => $valueIntput[ $GLOBALS['constDataHeaders']['zip'] ],
    $GLOBALS['constDataHeaders']['state']   => $valueIntput[ $GLOBALS['constDataHeaders']['state'] ],
    $GLOBALS['constDataHeaders']['country'] => $valueIntput[ $GLOBALS['constDataHeaders']['country'] ],
    $GLOBALS['constDataHeaders']['lat']     => $arCoordinates['lat'],
    $GLOBALS['constDataHeaders']['lng']     => $arCoordinates['lng'] );
  }


  # Loop through Input Data Array, check if an item already has been cached (and geo fetched)
  function fetch_coordinates_loop($arDataInput, $arDataCache) {
    $arDataCache_additions = null;
    $count=0;
    foreach ($arDataInput as $keyInput => $valueInput) {
      echo "**Processing: " . $valueInput[ $GLOBALS['constDataHeaders']['date'] ] . ' ' . $valueInput[ $GLOBALS['constDataHeaders']['city'] ] . ' ' . $valueInput[ $GLOBALS['constDataHeaders']['zip'] ] . ' ' . $valueInput[ $GLOBALS['constDataHeaders']['state'] ] . ' ' . $valueInput[ $GLOBALS['constDataHeaders']['country'] ];
      if (check_if_input_exists_in_cache($valueInput, $arDataCache)) {
        echo "[Cached]\n";
      } else {
        echo "[Not Cached]\n";
        echo "- Todo: add item to cache\n";
        $arCoordinates = fetch_coordinates($valueInput);
        if ($arCoordinates==false) break; //something happened. Stop the loop. Work is done for today...
        $arDataCache_additions[] = createDataCacheItem($valueInput, $arCoordinates);
        $count++;
        if ($count>1000) {
          echo "Limit reached (1,000 records). (Google's limit  = 2,500 requests per 24 hour period.)";
          break;
        }
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
