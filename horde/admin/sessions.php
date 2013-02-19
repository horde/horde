<?php
/**
 * Sessions information.
 *
 * Copyright 2005-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:sessions')
));

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Horde_Core_View_Helper_Image');
$view->addHelper('Text');

try {
    $resolver = $injector->getInstance('Net_DNS2_Resolver');
    $s_info = array();

    foreach ($session->sessionHandler->getSessionsInfo() as $id => $data) {
        $tmp = array(
            'auth' => implode(', ', $data['apps']),
            'browser' => $data['browser'],
            'id' => $id,
            'remotehost' => '[' . _("Unknown") . ']',
            'timestamp' => date('r', $data['timestamp']),
            'userid' => $data['userid']
        );

        if (!empty($data['remoteAddr'])) {
            $host = null;
            if ($resolver) {
                try {
                    if ($resp = $resolver->query($data['remoteAddr'], 'PTR')) {
                        $host = $resp->answer[0]->ptrdname;
                    }
                } catch (Net_DNS2_Exception $e) {}
            }
            if (is_null($host)) {
                $host = @gethostbyaddr($data['remoteAddr']);
            }
            $tmp['remotehost'] = $host . ' [' . $data['remoteAddr'] . '] ';
            $tmp['remotehostimage'] = Horde_Core_Ui_FlagImage::generateFlagImageByHost($host);
        }

        $s_info[] = $tmp;
    }

    $view->session_info = $s_info;
} catch (Horde_Exception $e) {
    $view->error = $e->getMessage();
}

$page_output->addInlineScript(array(
    '$$("DIV.sesstoggle").invoke("observe", "click", function() { [ this.nextSiblings(), this.immediateDescendants() ].flatten().compact().invoke("toggle"); })'
), true);
$page_output->header(array(
    'title' => _("Session Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('sessions');
$page_output->footer();
