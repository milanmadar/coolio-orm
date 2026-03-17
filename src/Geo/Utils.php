<?php

namespace Milanmadar\CoolioORM\Geo;

class Utils
{
    /**
     * Automatically calculates the correct UTM SRID for a WGS84 coordinate.
     * @param float $lon WGS84 Longitude
     * @param float $lat WGS84 Latitude
     * @return int The appropriate EPSG SRID (326XX for North, 327XX for South)
     */
    public static function getUtmSridFromWGS(float $lon, float $lat): int
    {
        if ($lat < -90 || $lat > 90) {
            // swap them
            $temp = $lat;
            $lat = $lon;
            $lon = $temp;
        }

        // Formula: Zone = floor((lon + 180) / 6) + 1
        $zone = floor(($lon + 180) / 6) + 1;

        // EPSG 32600 block for Northern Hemisphere, 32700 for Southern
        return (int)(
            ($lat >= 0) ? (32600 + $zone) : (32700 + $zone)
        );
    }
}