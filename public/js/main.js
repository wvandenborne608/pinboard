/*
 * main functions
 */


function initialize(displayType, defaultLat, defaultlong, defaultZoom, defaultCanvas) {
  $(document).ready(function() {
      $.ajax({
          type: "GET",
          url: "data/input.csv",
          dataType: "text",
          async: true,
          displayType: this.displayType,
          defaultLat: this.defaultLat,
          defaultlong: this.defaultlong,
          defaultZoom: this.defaultZoom,
          defaultCanvas: this.defaultCanvas,
          beforeSend: function(){
            $( "body" ).addClass("loading");
          },
          success: function(dataInput) {
            arDataInput = CSVToArray(dataInput);
            $(document).ready(function() {
              $.ajax({
                type: "GET",
                url: "data/cache.csv",
                dataType: "text",
                async: true,
                arDataInput: arDataInput,
                displayType: displayType,
                defaultLat: defaultLat,
                defaultlong: defaultlong,
                defaultZoom: defaultZoom,
                defaultCanvas: defaultCanvas,
                success: function(dataCache) {
                  arDataCache = CSVToArray(dataCache);
                  switch (this.displayType) {
                    case "heatmap2":
                      showHeatMap2(this.arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas);
                      break;
                    case "heatmap":
                      showHeatMap(this.arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas);
                      break;
                    case "markermap":
                      showMarkerMap(this.arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas);
                      break;
                    case "clustermap":
                      showMarkerClusterMap(this.arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas);
                      break;
                  }
                  $( "body" ).removeClass("loading");
                }
             });
           });
          }
      });
  });
}

function CSVToArray( strData, strDelimiter ){
  strDelimiter = (strDelimiter || ";");
  var objPattern = new RegExp(
    (
      "(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +
      "(?:\"([^\"]*(?:\"\"[^\"]*)*)\"|" +
      "([^\"\\" + strDelimiter + "\\r\\n]*))"
    ),
    "gi"
    );
  var arrData = [[]];
  var arrMatches = null;
  while (arrMatches = objPattern.exec( strData )){
    var strMatchedDelimiter = arrMatches[ 1 ];
    if (
      strMatchedDelimiter.length &&
      (strMatchedDelimiter != strDelimiter)
      ){
      arrData.push( [] );
    }
    if (arrMatches[ 2 ]){
      var strMatchedValue = arrMatches[ 2 ].replace(
        new RegExp( "\"\"", "g" ),
        "\""
        );
    } else {
      var strMatchedValue = arrMatches[ 3 ];
    }
    arrData[ arrData.length - 1 ].push( strMatchedValue );
  }
  return arrData;
}


function fetchCoordinates(i, arDataInput, arDataCache) {
  var arCoordinates = new Array();
  var dataInputLength = arDataInput.length;
  var dataCacheLength = arDataCache.length;
  for (var j = 0; j < dataCacheLength; j++) {
    if ((arDataCache[j][0] == arDataInput[i][0])
    && (arDataCache[j][1] == arDataInput[i][1])
    && (arDataCache[j][2] == arDataInput[i][2])
    && (arDataCache[j][3] == arDataInput[i][3])) {
      arCoordinates[0]=arDataCache[j][4];
      arCoordinates[1]=arDataCache[j][5];
      return arCoordinates;
    }
  }
  return false;
}


function showHeatMap2(arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas) {
  var map, pointarray, heatmap;
  var dataInputLength = arDataInput.length;
  var mapData={
    max: 46,
    data: []
  };
  for (var i = 0; i < dataInputLength; i++) {
  	var arCoordinates = fetchCoordinates(i, arDataInput, arDataCache);
  	mapData.data.push( {lat: arCoordinates[0], lng:arCoordinates[1], count: 1} );
  }
  var mapOptions = {
    zoom: defaultZoom,
    center: new google.maps.LatLng(defaultLat, defaultlong),
    mapTypeId: google.maps.MapTypeId.SATELLITE
  };
  map = new google.maps.Map(document.getElementById(defaultCanvas), mapOptions);
  var heatmap = new HeatmapOverlay(map, {
      "radius":30,
      "visible":true, 
      "opacity":100,
      "gradient": { 0.45: "rgb(0,0,255)", 0.55: "rgb(0,255,255)", 0.65: "rgb(0,255,0)", 0.95: "yellow", 1.0: "rgb(255,0,0)" }
  });
  google.maps.event.addListenerOnce(map, "idle", function(){
      heatmap.setDataSet(mapData);
  });
}


function showHeatMap(arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas) {
  var map, pointarray, heatmap;
  var dataInputLength = arDataInput.length;
  var mapData = [];
  for (var i = 0; i < dataInputLength; i++) {
  	var arCoordinates = fetchCoordinates(i, arDataInput, arDataCache);
  	mapData.push( new google.maps.LatLng(arCoordinates[0],arCoordinates[1]) );
  }
  var mapOptions = {
    zoom: defaultZoom,
    center: new google.maps.LatLng(defaultLat, defaultlong),
    mapTypeId: google.maps.MapTypeId.SATELLITE
  };
  map = new google.maps.Map(document.getElementById(defaultCanvas), mapOptions);
  var pointArray = new google.maps.MVCArray(mapData);
  heatmap = new google.maps.visualization.HeatmapLayer({
    data: pointArray
  });
  heatmap.setMap(map);
}


function showMarkerClusterMap(arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas) {
  var center = new google.maps.LatLng(defaultLat, defaultlong);
  var map = new google.maps.Map(document.getElementById(defaultCanvas), {
      zoom: defaultZoom,
      center: center,
      mapTypeId: google.maps.MapTypeId.SATELLITE
  });
  var markers = [];
  var dataInputLength = arDataInput.length;
  var mapData = [];
  for (var i = 0; i < dataInputLength; i++) {
  	var arCoordinates = fetchCoordinates(i, arDataInput, arDataCache);
    var latLng = new google.maps.LatLng(arCoordinates[0],arCoordinates[1]);
    var marker = new google.maps.Marker({
         position: latLng,
         map: map
    });
  	markers.push(marker);
  }
  var markerCluster = new MarkerClusterer(map, markers);
}


function showMarkerMap(arDataInput, arDataCache, defaultLat, defaultlong, defaultZoom, defaultCanvas) {
  var center = new google.maps.LatLng(defaultLat, defaultlong);
  var image = 'img/measle_orange.png';
  var map = new google.maps.Map(document.getElementById(defaultCanvas), {
      zoom: defaultZoom,
      center: center,
      mapTypeId: google.maps.MapTypeId.SATELLITE
  });
  var markers = [];
  var dataInputLength = arDataInput.length;
  var mapData = [];
  for (var i = 0; i < dataInputLength; i++) {
  	var arCoordinates = fetchCoordinates(i, arDataInput, arDataCache);
    var latLng = new google.maps.LatLng(arCoordinates[0],arCoordinates[1]);
    var marker = new google.maps.Marker({
         position: latLng,
         map: map,
         icon: image
    });
  	markers.push(marker);
  }
}

