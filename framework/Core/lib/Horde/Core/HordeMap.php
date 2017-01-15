<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2009-2017 Horde LLC (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * HordeMap.
 *
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC (http://www.horde.org/)
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 * @since     2.12.0
 */
class Horde_Core_HordeMap
{
    /**
     * Initialize a HordeMap.
     *
     * @param array $params
     */
    public static function init(array $params = array())
    {
        global $browser, $conf, $language, $page_output, $registry;

        // Language specific file needed?
        $language = str_replace('_', '-', $language);
        if (!file_exists($registry->get('jsfs', 'horde') . '/map/lang/' . $language . '.js')) {
            $language = 'en-US';
        }

        $params = array_merge(array(
            'conf' => array(
                'language' => $language,
                'markerImage' => strval(Horde_Themes::img('map/marker.png')),
                'markerBackground' => strval(Horde_Themes::img('map/marker-shadow.png')),
                'useMarkerLayer' => true,
            ),
            'driver' => 'Horde',
            'geocoder' => $conf['maps']['geocoder'],
            'jsuri' => $registry->get('jsuri', 'horde') . '/map/',
            'providers' => $conf['maps']['providers'],
            'ssl' => $browser->usingSSLConnection(),
        ), $params);

        foreach ($params['providers'] as $layer) {
            switch ($layer) {
            case 'Google':
                $params['conf']['apikeys']['google'] = $conf['api']['googlemaps'];
                break;
            }
        }

        if (!empty($params['geocoder'])) {
            switch ($params['geocoder']) {
            case 'Google':
                $params['conf']['apikeys']['google'] = $conf['api']['googlemaps'];
                break;
            }
        }

        $page_output->addScriptFile('map/map.js', 'horde');
        $page_output->addInlineScript(array(
            'HordeMap.initialize(' . Horde_Serialize::serialize($params, HORDE_SERIALIZE::JSON) . ');'
        ));
    }

}
