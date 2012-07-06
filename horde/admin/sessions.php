<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:sessions')
));

$page_output->addInlineScript(array(
    '$$("DIV.sesstoggle").invoke("observe", "click", function() { [ this.nextSiblings(), this.immediateDescendants() ].flatten().compact().invoke("toggle"); })'
), true);
$page_output->header(array(
    'title' => _("Session Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';

echo '<h1 class="header">' . _("Current Sessions");
try {
    $session_info = $session->sessionHandler->getSessionsInfo();

    echo ' (' . count($session_info) . ')</h1>' .
         '<ul class="headerbox linedRow">';

    $plus = Horde::img('tree/plusonly.png', _("Expand"));
    $minus = Horde::img('tree/minusonly.png', _("Collapse"), 'style="display:none"');

    $resolver = $injector->getInstance('Net_DNS2_Resolver');

    foreach ($session_info as $id => $data) {
        $entry = array(
            _("Session Timestamp:") => date('r', $data['timestamp']),
            _("Browser:") => $data['browser'],
            _("Remote Host:") => _("[Unknown]"),
            _("Authenticated to:") => implode(', ', $data['apps'])
        );

        if (!empty($data['remoteAddr'])) {
            $host = null;
            if ($resolver) {
                try {
                    if ($response = $resolver->query($data['remoteAddr'], 'PTR')) {
                        $host = $response->answer[0]->ptrdname;
                    }
                } catch (Net_DNS2_Exception $e) {}
            }
            if (is_null($host)) {
                $host = @gethostbyaddr($data['remoteAddr']);
            }
            $entry[_("Remote Host:")] = $host . ' [' . $data['remoteAddr'] . '] ' . Horde_Core_Ui_FlagImage::generateFlagImageByHost($host);
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

$page_output->footer();
