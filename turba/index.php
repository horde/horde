<?php
/**
 * Turba index page.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

require TURBA_BASE . '/' . ($browse_source_count
                            ? basename($prefs->getValue('initial_page'))
                            : 'search.php');
