<?php
/**
 * Geocode client for the Geonames API.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Imple_Geocoder_Geonames extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected function _attach($init)
    {
        return false;
    }

    /**
     * @TODO: For reverse requests come up with a reasonable algorithm for
     *        checking if we have a lat/lng in the US since the
     *        findNearestAddress method is US only. If non-us, fallback to a
     *        findNearest or findPostalcode or similar request. Also will need
     *        to normalize the various response structures.
     *
     * 'locations' will trigger a forward geocoding request.
     * 'lat' and 'lon' will trigger a reverse geocoding request.
     *
     * @throws Horde_Exception
     */
    protected function _handle(Horde_Variables $vars)
    {
        if ($vars->location) {
            $url = new Horde_Url('http://ws.geonames.org/searchJSON');
            $url->add(array(
                'q' => $vars->location
            ));
        } elseif ($vars->lat && $vars->lon) {
            $url = new Horde_Url('http:/ws.geonames.org/findNearestJSON');
            $url->add(array(
                'lat' => $vars->lat,
                'lng' => $vars->lon
            ));
        } else {
            throw new Horde_Exception('Incorrect parameters');
        }

        $response = $GLOBALS['injector']->getInstance('Horde_Core_Factory_HttpClient')->create()->get($url);

        return new Horde_Core_Ajax_Response_Prototypejs(array(
            'results' => $response->getBody(),
            'status' => 200
        ));
    }

}
