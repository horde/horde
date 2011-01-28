<?php
/**
 * Script to handle requests for html delivery of stories.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

/* Get the id and format of the channel to display. */
$criteria = Horde_Util::nonInputVar('criteria');
if (!$criteria) {
    $criteria['feed'] = Horde_Util::getFormData('channel_id');
    $criteria['format'] = Horde_Util::getFormData('format');
}
if (empty($criteria['format'])) {
    // Select the default channel format
    // TODO: FIXME
    $criteria['format'] = 'standard';
}

$params = array('registry' => &$registry,
                'notification' => &$notification,
                'conf' => &$conf,
                'criteria' => &$criteria);
$view = new Jonah_View_DeliveryHtml($params);
$view->run();
