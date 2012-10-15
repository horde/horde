<?php
/**
 * Turba browse.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

/* If default source is not browsable, try one from the addressbooks pref */
if (empty($cfgSources[Turba::$source]['browse'])) {
    $addressbooks = Turba::getAddressBooks();
    foreach ($addressbooks as $source) {
        if (!empty($cfgSources[$source]['browse'])) {
            Turba::$source = $source;
            break;
        }
    }
}

$params = array(
    'vars' => Horde_Variables::getDefaultVariables(),
    'prefs' => &$prefs,
    'notification' => &$notification,
    'registry' => &$registry,
    'browse_source_count' => $browse_source_count,
    'copymoveSources' => $copymoveSources,
    'addSources' => $addSources,
    'cfgSources' => $cfgSources,
    'attributes' => $attributes,
    'turba_shares' => $injector->getInstance('Turba_Shares'),
    'conf' => $conf,
    'source' => Turba::$source,
    'browser' => $browser
);

$browse = new Turba_View_Browse($params);
$browse->run();
