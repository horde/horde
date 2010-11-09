<script type="text/javascript">
function toggleTags(domid)
{
    $('tag-show_' + domid).toggle();
    $('tag-hide_' + domid).toggle();
    $('tagnode_' + domid).toggle();
}
</script>
<?php
Horde::addScriptFile('quickfinder.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
Horde::addScriptFile('calendar-panel.js', 'kronolith');

$info_img = Horde::img('info_icon.png', _("Calendar Information"), array('class' => 'calendar-info'));

$current_user = $GLOBALS['registry']->getAuth();
$my_calendars = array();
$shared_calendars = array();
foreach (Kronolith::listInternalCalendars() as $id => $cal) {
    if ($cal->get('owner') && $cal->get('owner') == $current_user) {
        $my_calendars[$id] = $cal;
    } else {
        if (!empty($conf['share']['hidden']) &&
            !in_array($cal->getName(), $display_calendars)) {
            continue;
        }
        $shared_calendars[$id] = $cal;
    }
}

$externals = array();
foreach ($all_external_calendars as $id => $calendar) {
    $app = $registry->get('name', $registry->hasInterface($calendar->api()));
    if (!empty($conf['share']['hidden']) &&
        !in_array($id, $display_external_calendars)) {
        continue;
    }
    $externals[$app][$id] = $calendar->name();
}

$tagger = Kronolith::getTagger();
?>
<div id="pageControls">
 <a id="sbarShow" href="#" onclick="sbarToggle(); return false;"><?php echo _("Calendars") ?></a>
 <a id="sbarHide" href="#" onclick="sbarToggle(); return false;"><?php echo _("Calendars") ?></a>
<?php if (strlen($GLOBALS['session']->get('kronolith', 'display_cal'))): ?>
   <a id="sbarClose" href="<?php echo Horde::selfUrl(true)->add('display_cal', '') ?>"><?php echo $GLOBALS['registry']->getAuth() ? _("Return to my calendars") : _("Return to calendars") ?></a>
<?php endif; ?>
<div id="pageControlsInner">
<?php if (strlen($GLOBALS['session']->get('kronolith', 'display_cal'))): ?>
  <h4><?php echo ngettext("Showing calendar:", "Showing calendars:", count($calendar_names)) ?></h4>
  <?php foreach ($calendar_names as $name): ?>
   <h4><?php echo $name ?></h4>
  <?php endforeach; ?>
<?php else: ?>
<form action="<?php echo Horde::selfUrl() ?>" method="post">
<?php echo Horde_Util::formInput() ?>
 <h4><?php echo _("Search for Calendars:") ?></h4>
 <p>
  <input id="calendarSearch" type="text" for="mycalendars,sharedcalendars,remotecalendars,externalcalendars" />
 </p>

<?php if ($GLOBALS['registry']->getAuth()): ?>
 <p>
  <a href="<?php echo Horde::url('calendars/') ?>"><?php echo _("[Manage Calendars]") ?></a><br />
  <a href="<?php echo Horde::url('resources/')?>"><?php echo ($GLOBALS['registry']->isAdmin() ? _("[Manage Resource Calendars]") : _("[Resource Calendars]"))?></a>
 </p>
<?php endif; ?>

<?php if (count($my_calendars)): ?>
 <h4><?php echo _("My Calendars:") ?></h4>
 <ul id="mycalendars">
<?php foreach ($my_calendars as $id => $cal): ?>
  <li<?php echo Kronolith::getCSSColors($cal) ?>><label><input type="checkbox" class="checkbox" name="display_cal[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_calendars) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($cal->get('name')) ?></label> <?php echo $info_img ?>
   <div class="panel-tags">
   <?php $tags = $tagger->getTags($id, 'calendar'); ?>
    <span style="cursor:pointer;" id="tag-show_<?php echo $id?>" onclick="toggleTags('<?php echo $id?>');" title="<?php echo _("Add new tags")?>"><?php echo Horde::img('plus.png')?></span><span id="tag-hide_<?php echo $id?>" style="display:none;cursor:pointer;" onclick="toggleTags('<?php echo $id?>');"><?php echo Horde::img('minus.png')?></span>
    <h5 style="display: inline;"><?php echo _("Tags")?></h5>
    <div id="tagnode_<?php echo $id?>" style="display:none;">
      <ul id="tags_<?php echo $id?>">
        <?php foreach ($tags as $tag_id => $tag): ?>
         <li class="panel-tags">
<?php
echo htmlspecialchars($tag);
if ($cal->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    echo '<a href="#" id="remove' . md5($id . $tag_id) . '">'
        . Horde::img('delete-small.png', _("Remove Tag"))
        . '</a>';
}
$GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('kronolith', 'TagActions'), array(
    'action' => 'delete',
    'resource' => $id,
    'tagId' => $tag_id,
    'triggerId' => 'remove' . md5($id . $tag_id),
    'type' => 'calendar'
));
?>
        </li>
        <?php endforeach;?>
      </ul>
      <div class="clear">&nbsp;</div>
      <input type="text" name="newtags-input_<?php echo $id?>" id="newtags-input_<?php echo $id?>" size="10" /><input type="button" class="button" value="add" name="newtags-button_<?php echo $id?>" id="newtags-button_<?php echo $id?>" />
      <span id="newtags-input_<?php echo $id?>_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
     <?php
      $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('kronolith', 'TagAutoCompleter'), array(
          'id' => $id,
          'triggerId' => 'newtags-input_' . $id
      ));
      $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create(array('kronolith', 'TagActions'), array(
          'action' => 'add',
          'resource' => $id,
          'triggerId' => 'newtags-button',
          'type' => 'calendar'
      ));
      ?>
  </div>
  </div>
  </li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (count($shared_calendars)): ?>
 <h4><?php echo _("Shared Calendars:") ?></h4>
 <ul id="sharedcalendars">
<?php foreach ($shared_calendars as $id => $cal): ?>
   <li<?php echo Kronolith::getCSSColors($cal) ?>><label><input type="checkbox" class="checkbox" name="display_cal[]" value="<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_calendars) ? ' checked="checked"' : '') . ' /> ' . ($cal->get('owner') ? '[' . htmlspecialchars($GLOBALS['registry']->convertUsername($cal->get('owner'), false)) . '] ' : '') . htmlspecialchars($cal->get('name')) ?></label> <?php echo $info_img ?></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (count($externals)): ?>
 <h4><?php echo _("External Events:") ?></h4>
 <ul id="externalcalendars">
 <?php foreach ($externals as $app => $categories): ?>
 <?php if (count($categories)): ?>
  <li class="category"><?php echo htmlspecialchars($app) ?></li>
   <?php foreach ($categories as $id => $name): ?>
   <li><label><input type="checkbox" class="checkbox" name="display_cal[]" value="external_<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_external_calendars) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($name) ?></label></li>
   <?php endforeach; ?>
 <?php endif; ?>
 <?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (count($all_remote_calendars)): ?>
 <h4><?php echo _("Remote Calendars:") ?></h4>
 <ul id="remotecalendars">
<?php foreach ($all_remote_calendars as $id => $cal): ?>
  <li<?php echo $cal->css() ?>><label><input type="checkbox" class="checkbox" name="display_cal[]" value="remote_<?php echo htmlspecialchars($cal->url()) ?>"<?php echo (in_array($cal->url(), $display_remote_calendars) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($cal->name()) ?></label> <?php echo $info_img ?></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<?php if (!empty($conf['holidays']['enable'])): ?>
 <h4><?php echo _("Holidays:") ?></h4>
 <ul id="holidays">
<?php foreach ($all_holidays as $id => $holiday): ?>
  <li<?php echo $holiday->css() ?>><label><input type="checkbox" class="checkbox" name="display_cal[]" value="holiday_<?php echo htmlspecialchars($id) ?>"<?php echo (in_array($id, $display_holidays) ? ' checked="checked"' : '') . ' /> ' . htmlspecialchars($holiday->name()) ?></label></li>
<?php endforeach; ?>
 </ul>
<?php endif; ?>

<noscript>
 <p>
  <input type="submit" value="<?php echo _("Save") ?>" class="button" />
 </p>
</noscript>
</form>

</div>
</div>
<?php endif; ?>
