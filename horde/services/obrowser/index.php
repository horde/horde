<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$path = Horde_Util::getFormData('path');

if (empty($path)) {
    $list = array();
    $apps = $registry->listApps(null, false, Horde_Perms::READ);
    foreach ($apps as $app) {
        if ($registry->hasMethod('browse', $app)) {
            $list[$app] = array('name' => $registry->get('name', $app),
                                'icon' => $registry->get('icon', $app),
                                'browseable' => true);
        }
    }
} else {
    $pieces = explode('/', $path);
    $list = $registry->callByPackage($pieces[0], 'browse', array('path' => $path));
}

if (!count($list)) {
    throw new Horde_Exception(_("Nothing to browse, go back."));
}

$tpl = <<<TPL
<script type="text/javascript">
function chooseObject(oid)
{
    if (!window.opener || !window.opener.obrowserCallback) {
        return false;
    }

    var result = window.opener.obrowserCallback(window.name, oid);
    if (!result) {
        window.close();
        return;
    }

    alert(result);
    return false;
}
</script>

<div class="header">
 <span class="rightFloat"><tag:close /></span>
 <gettext>Object Browser</gettext>
</div>
<div class="headerbox">
 <table class="striped" cellspacing="0" style="width:100%">
 <loop:rows>
  <tr>
   <td>
    <tag:rows.icon />
    <tag:rows.name />
   </td>
  </tr>
 </loop:rows>
 </table>
</div>
TPL;

$rows = array();
foreach ($list as $path => $values) {
    $row = array();

    // Set the icon.
    if (!empty($values['icon'])) {
        $row['icon'] = Horde::img($values['icon'], $values['name'], '', '');
    } elseif (!empty($values['browseable'])) {
        $row['icon'] = Horde::img('tree/folder.png');
    } else {
        $row['icon'] = Horde::img('tree/leaf.png');
    }

    // Set the name/link.
    if (!empty($values['browseable'])) {
        $url = Horde::url('services/obrowser', false, array('app' => 'horde'))->add('path', $path);
        $row['name'] = $url->link() . htmlspecialchars($values['name']) . '</a>';
    } else {
        $js = "return chooseObject('" . addslashes($path) . "');";
        $row['name'] = Horde::link('#', sprintf(_("Choose %s"), $values['name']), '', '', $js) . htmlspecialchars($values['name']) . '</a>';
    }

    $rows[] = $row;
}

$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);
$template->set('rows', $rows);
$template->set('close', '<a href="#" onclick="window.close(); return false;">' . Horde::img('close.png') . '</a>');

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header();
echo $template->parse($tpl);
$page_output->footer();
