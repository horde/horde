<?php
/**
 * $Id: index.php 220 2008-01-11 11:46:13Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
require_once dirname(__FILE__) . '/../lib/base.php';
require_once 'Horde/Loader.php';
require_once './lib/Block/Layout/View/js.php';

// Load layout from preferences.
$layout_pref = unserialize($prefs->getValue('portal_layout'));
if (!is_array($layout_pref)) {
    $layout_pref = array();
}
if (!count($layout_pref)) {
    $layout_pref = Horde_Block_Collection::getFixedBlocks();
}

// Render layout.
$view = new Horde_Block_Layout_View_Js($layout_pref);
$layout_html = $view->toHtml();

$title = _("Edit yout profile page");

Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/menu/menu.inc';
?>

<script type="text/javascript" src="js/builder.js"></script>
<script type="text/javascript" src="js/dragdrop.js"></script>
<script type="text/javascript" src="js/src/portal.js"></script>
<script type="text/javascript" src="js/src/portal_edit.js"></script>

<link href="themes/screen.css" rel="stylesheet" type="text/css" />
</head>

<div id="menuBottom">
    <a href="#" onclick="listWidgets()"><?php echo _("Add content") ?></a> |
    <a href="#" onclick="alert('TODO')"><?php echo _("Reset to default") ?></a> |
    <a href="#" onclick="savePortal()"><?php echo _("Save") ?></a> |
</div>
<br class="clear" />
 <div id="control_buttons" style="display: none">
   <a href="#" onclick="minimizeWidget(this); return false;" id="minimize_button" title="<?php echo _("Minimize") ?>"></a>
   <a href="#" onclick="editWidget(this); return false;" id="edit_button" title="<?php echo _("Edit") ?>"></a>
   <a href="#" onclick="reloadWidget(this); return false;" id="reload_button" title="<?php echo _("Reload") ?>"></a>
   <a href="#" onclick="removeWidget(this); return false;" id="delete_button" title="<?php echo _("Delete") ?>"></a>
 </div>

<?php

echo $layout_html;

require $registry->get('templates', 'horde') . '/common-footer.inc';
