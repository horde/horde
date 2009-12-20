<?php
/**
 * Geocode client for the Geonames API.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Ajax
 */
class Horde_Ajax_Imple_Geocoder_Geonames extends Horde_Ajax_Imple_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'triggerId' => (string) [optional] TODO
     * </pre>
     */
    public function __construct($params)
    {
        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
    }


    /**
     * Handle the geocoding request.
     *
     * @TODO: For reverse requests come up with a reasonable algorithm for
     *        checking if we have a lat/lng in the US since the
     *        findNearestAddress method is US only. If non-us, fallback to a
     *        findNearest or findPostalcode or similar request. Also will need
     *        to normalize the various response structures.
     *
     * $args['locations'] will trigger a forward geocoding request.
     * $args['lat'] and $args['lon'] will trigger a reverse geocoding request.
     *
     * @see framework/Ajax/lib/Horde/Ajax/Imple/Horde_Ajax_Imple_Base#handle($args, $post)
     * @throws Horde_Exception
     */
    public function handle($args, $post)
    {
        if ($args['location']) {
            $url = Horde_Util::addParameter('http://ws.geonames.org/searchJSON', 'q', $args['location']);
        } elseif (!empty($args['lat']) && !empty($args['lon'])) {
            $url = Horde_Util::addParameter('http:/ws.geonames.org/findNearestJSON', array('lat' => $args['lat'], 'lng' => $args['lon']));
        }
        $client = new Horde_Http_Client();
        try {
            $response = $client->get($url);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Exception($e);
        }
        return array('status' => 200,
                     'results' => $response->getBody());
    }

}
