<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$title = _("Session Admin");
Horde::addScriptFile('prototype.js', 'horde');
Horde::addInlineScript(array(
    '$$("DIV.sesstoggle").invoke("observe", "click", function() { [ this.nextSiblings(), this.immediateDescendants() ].flatten().compact().invoke("toggle"); })'
), 'dom');

require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

echo '<h1 class="header">' . _("Current Sessions");
try {
    if (!isset($registry->sessionHandler)) {
        throw new Horde_Exception(_("Session handler does not support listing active sessions."));
    }

    $session_info = $registry->sessionHandler->getSessionsInfo();

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
            _("Remote Host:") => _("[Unknown]"),
            _("Authenticated to:") => implode(', ', $data['apps'])
        );

        if (!empty($data['remoteAddr'])) {
            if (class_exists('Net_DNS')) {
                $response = $resolver->query($data['remoteAddr'], 'PTR');
                $host = $response ? $response->answer[0]->ptrdname : $data['remoteAddr'];
            } else {
                $host = @gethostbyaddr($data['remoteAddr']);
            }
            $entry[_("Remote Host:")] = $host . ' [' . $data['remoteAddr'] . '] ' . Horde_Nls::generateFlagImageByHost($host, $injector->getInstance('Net_DNS_Resolver'));
        }

        echo '<li><div class="sesstoggle">' . $plus . $minus . htmlspecialchars($data['userid']) . ' [' . htmlspecialchars($id) . ']'
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
