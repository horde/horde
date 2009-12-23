<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/base.php';

if (!Horde_Auth::isAdmin()) {
    Horde::authenticationFailureRedirect();
}

$type = !empty($conf['sessionhandler']['type']) ? $conf['sessionhandler']['type'] : 'none';
if ($type == 'external') {
    $notification->push(_("Cannot administer external session handlers."), 'horde.error');
} else {
    $sh = Horde_SessionHandler::singleton($type);
}

$title = _("Session Admin");
Horde::addScriptFile('prototype.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

if (empty($sh)) {
    require HORDE_TEMPLATES . '/common-footer.inc';
    exit;
}

echo '<h1 class="header">' . _("Current Sessions");
try {
    $session_info = $sh->getSessionsInfo();

    echo ' (' . count($session_info) . ')</h1>' .
         '<ul class="headerbox linedRow">';

    $plus = Horde::img('tree/plusonly.png', _("Expand"), '', $GLOBALS['registry']->getImageDir('horde'));
    $minus = Horde::img('tree/minusonly.png', _("Collapse"), 'style="display:none"', $GLOBALS['registry']->getImageDir('horde'));

    if (class_exists('Net_DNS')) {
        $resolver = new Net_DNS_Resolver();
        $resolver->retry = isset($GLOBALS['conf']['dns']['retry']) ? $GLOBALS['conf']['dns']['retry'] : 1;
        $resolver->retrans = isset($GLOBALS['conf']['dns']['retrans']) ? $GLOBALS['conf']['dns']['retrans'] : 1;
    }

    foreach ($session_info as $id => $data) {
        $entry = array(
            _("Session Timestamp:") => date('r', $data['timestamp']),
            _("Browser:") => $data['browser'],
            _("Remote Host:") => _("[Unknown]")
        );

        if (!empty($data['remote_addr'])) {
            if (class_exists('Net_DNS')) {
                $response = $resolver->query($data['remote_addr'], 'PTR');
                $host = $response ? $response->answer[0]->ptrdname : $data['remote_addr'];
            } else {
                $host = @gethostbyaddr($data['remote_addr']);
            }
            $entry[_("Remote Host:")] = $host . ' [' . $data['remote_addr'] . '] ' . Horde_Nls::generateFlagImageByHost($host);
        }

        echo '<li><div onclick="$(this).nextSiblings().invoke(\'toggle\'); $(this).immediateDescendants().invoke(\'toggle\');">' . $plus . $minus . htmlspecialchars($data['userid']) . ' [' . htmlspecialchars($id) . ']'
            . '</div><div style="padding-left:20px;display:none">';
        foreach ($entry as $key => $val) {
            echo '<div><strong>' . $key . '</strong> ' . $val . '</div>';
        }
        echo '</div></li>';
    }
    echo '</ul>';
} catch (Horde_Exception $e) {
    echo '</h1><p class="headerbox"><em>' . sprintf(_("Listing sessions failed: %s"), $e->getMessage()) . '</em></p>';
}

require HORDE_TEMPLATES . '/common-footer.inc';
