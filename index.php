<?php

define('GOOGLE_API_KEY', 'enter-your-key');

class Locator
{
    public static function isInsideRadius($addressString, $IPaddress, $distanceInKm = 100)
    {
        if (!$addressString || !$IPaddress || $distanceInKm <= 0) {
            return null;
        }
        $record = geoip_record_by_name($IPaddress);
        if ($record) {
            if (!empty($record['latitude']) && !empty($record['longitude'])) {
                try {
                    $addressString = str_replace(' ', '+', $addressString);
                    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . $addressString . '&key=' . GOOGLE_API_KEY;
                    $get_content = file_get_contents($url);
                    $coordination = json_decode($get_content, true);
                    if ($coordination['status'] != 'OK') {
                        return null;
                    }

                    $LatLng = array();
                    foreach ($coordination['results'] as $coord) {
                        $LatLng = array(
                            'latitude' => $coord['geometry']['location']['lat'],
                            'longitude' => $coord['geometry']['location']['lng']
                        );
                    }

                    if (empty($LatLng)) {
                        return null;
                    }

                    $distance = static::distance($record['latitude'], $record['longitude'], $LatLng['latitude'], $LatLng['longitude'], false);

                    return $distance < $distanceInKm;
                } catch (Exception $e) {
                    return null;
                }
            }
        }
        return null;
    }

    private static function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
    {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;

        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;

        return ($miles ? ($km * 0.621371192) : $km);
    }
}

$result = Locator::isInsideRadius('Paris', '90.100.120.130');