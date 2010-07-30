<?php
/**
 * Turba browse.php.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Turba
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

/* If default source is not browsable, try one from the addressbooks pref */
if (empty($cfgSources[$default_source]['browse'])) {
    $addressbooks = Horde_Serialize::unserialize($prefs->getValue('addressbooks'), Horde_Serialize::JSON);
    foreach ($addressbooks as $source) {
        if (!empty($cfgSources[$source]['browse'])) {
            $default_source = $source;
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
    'browse_source_options' => $browse_source_options,
    'copymove_source_options' => $copymove_source_options,
    'copymoveSources' => $copymoveSources,
    'addSources' => $addSources,
    'cfgSources' => $cfgSources,
    'attributes' => $attributes,
    'turba_shares' => isset($turba_shares) ? $turba_shares : null,
    'conf' => $conf,
    'source' => $default_source,
    'browser' => $browser
);

$browse = new Turba_View_Browse($params);
$browse->run();
