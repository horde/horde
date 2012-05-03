<?php

require_once NAG_TEMPLATES . '/javascript_defs.php';

global $page_output;
$page_output->addScriptFile('scriptaculous/effects.js', 'horde');
$page_output->addScriptFile('redbox.js', 'horde');
$page_output->addScriptFile('tasklist-panel.js');

$info_img = Horde::img('info_icon.png', _("Task List Information"), array('class' => 'tasklist-info'));

$current_user = $GLOBALS['registry']->getAuth();
$my_tasklists = array();
$shared_tasklists = array();
foreach (Nag::listTasklists() as $id => $tl) {
    if ($tl->get('owner') && $tl->get('owner') == $current_user) {
        $my_tasklists[$id] = $tl;
    } else {
        $shared_tasklists[$id] = $tl;
    }
}
?>

<div id="pageControls">
<a id="sbarShow" href="#" onclick="sbarToggle(); return false;"><?php echo _("Task Lists") ?></a>
<a id="sbarHide" href="#" onclick="sbarToggle(); return false;"><?php echo _("Task Lists") ?></a>

<div id="pageControlsInner">
<form action="<?php echo Horde::selfUrl() ?>" method="post">
<?php echo Horde_Util::formInput() ?>
 <h4><?php echo _("Search for Task Lists:") ?></h4>
 <p>
  <input id="tasklistSearch" type="text" for="mytasklists,sharedtasklists" />
 </p>

<?php if ($GLOBALS['registry']->getAuth()): ?>
 <p>
  <a href="<?php echo Horde::url('tasklists/') ?>"><?php echo _("[Manage Task Lists]") ?></a>
 </p>
<?php endif; ?>

<?php if ($current_user): ?>
 <h4><?php echo _("My Task Lists:") ?></h4>
 <ul id="mytasklists">
<?php foreach ($my_tasklists as $id => $tl): ?>
  <li><label><input type="checkbox" class="checkbox" name="display_tasklist[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_tasklists) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($tl->get('name')) ?></label> <?php echo $info_img ?></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (count($shared_tasklists)): ?>
 <h4><?php echo _("Shared Task Lists:") ?></h4>
 <ul id="sharedtasklists">
<?php foreach ($shared_tasklists as $id => $tl): ?>
   <li><label><input type="checkbox" class="checkbox" name="display_tasklist[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_tasklists) ? ' checked="checked"' : '') . ' /> ' . ($tl->get('owner') ? '[' . htmlspecialchars($registry->convertUsername($tl->get('owner'), false)) . '] ' : '') . htmlspecialchars($tl->get('name')) ?></label> <?php echo $info_img ?></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

 <p>
  <input type="submit" value="<?php echo _("Save") ?>" class="button" />
 </p>
</form>

</div>
</div>
