Pinboard
========

Plot measles on a google map based on addresses. 
Addresses are converted to geo coordinates and cached.


Instructions
-------------
1) Add addresses to: "/data/input.csv".
2) Run command-line script "/script/fetch_coordinates.php".
3) (The script will update "/data/cache.csv".)
4) Open "/public/index.html" in your browser.


Known issues
------------
* Fetch_coordinates.php uses a free Google API that is limited to 2500 requests per 24 hour.
* Fetch_coordinates.php will halt when the API can't convert an address. Then remove that address from "/data/input.csv" or manually add the geo-coordinate entry in "/data/cache.csv".
