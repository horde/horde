<?php
Horde::addScriptFile('quickfinder.js', 'horde');

$current_user = $GLOBALS['registry']->getAuth();
$my_classes = array();
$shared_classes = array();
foreach (Skoli::listClasses() as $id => $cl) {
    if ($cl->get('owner') && $cl->get('owner') == $current_user) {
        $my_classes[$id] = $cl;
    } else {
        $shared_classes[$id] = $cl;
    }
}
?>

<div id="pageControls">
<script type="text/javascript">
function sbarToggle()
{
    var pref_value;
    if (Element.hasClassName(document.body, 'rightPanel')) {
        pref_value = 0;
        Element.removeClassName(document.body, 'rightPanel');
    } else {
        pref_value = 1;
        Element.addClassName(document.body, 'rightPanel');
    }

    new Ajax.Request('<?php echo Horde::getServiceLink('prefsapi', 'skoli') ?>', { parameters: { pref: 'show_panel', value: pref_value } });
}
</script>
<a id="sbarShow" href="#" onclick="sbarToggle(); return false;"><?php echo _("Classes") ?></a>
<a id="sbarHide" href="#" onclick="sbarToggle(); return false;"><?php echo _("Classes") ?></a>

<div id="pageControlsInner">
<form action="<?php echo Horde::selfUrl() ?>" method="post">
<?php echo Horde_Util::formInput() ?>
 <h4><?php echo _("Search for Classes:") ?></h4>
 <p>
  <input id="classSearch" type="text" for="myclasses,sharedclasses" />
 </p>
 <p>
  <label><input type="checkbox" class="checkbox" name="show_students" value="1"<?php echo ($prefs->getValue('show_students') ? ' checked="checked"' : '') . ' /> ' . _("Show students?") ?></label>
 </p>

<?php if ($GLOBALS['registry']->getAuth()): ?>
 <p>
  <a href="<?php echo Horde::url('classes/') ?>"><?php echo _("[Manage Classes]") ?></a>
 </p>
<?php endif; ?>

<?php if (count($my_classes)): ?>
 <h4><?php echo _("My Classes:") ?></h4>
 <ul id="myclasses">
<?php foreach ($my_classes as $id => $cl): ?>
  <li><label><input type="checkbox" class="checkbox" name="display_class[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_classes) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($cl->get('name')) ?></label></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (count($shared_classes)): ?>
 <h4><?php echo _("Shared Classes:") ?></h4>
 <ul id="sharedclasses">
<?php foreach ($shared_classes as $id => $cl): ?>
   <li><label><input type="checkbox" class="checkbox" name="display_class[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_classes) ? ' checked="checked"' : '') . ' /> ' . ($cl->get('owner') ? '[' . htmlspecialchars($registry->convertUsername($cl->get('owner'), false)) . '] ' : '') . htmlspecialchars($cl->get('name')) ?></label></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

 <p>
  <input type="submit" value="<?php echo _("Save") ?>" class="button" />
 </p>
</form>

</div>
</div>
