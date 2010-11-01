<div id="page">

<h1 class="header">
 <?php echo _("Manage Calendars") ?>
</h1>

<div id="calendar-list-buttons">
 <?php if (!$prefs->isLocked('default_share')): ?>
 <form method="get" action="create.php">
  <?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Local Calendar") ?>" />
 </form>
 <?php endif; ?>

 <form method="get" action="remote_subscribe.php">
  <?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Subscribe to a Remote Calendar") ?>" />
 </form>
</div>

<table summary="<?php echo _("Calendar List") ?>" cellspacing="0" id="calendar-list" class="striped sortable">
 <thead>
  <tr>
   <th class="sortdown"><?php echo _("Calendar") ?></th>
   <th><?php echo _("Kind") ?></th>
   <th class="calendar-list-url nosort"><?php echo _("Display URL") ?></th>
   <th class="calendar-list-url nosort"><?php echo _("Subscription URL") ?></th>
   <th class="calendar-list-icon nosort" colspan="<?php echo empty($conf['share']['no_sharing']) ? 3 : 2 ?>">&nbsp;</th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_calendars) as $calendar_id): ?>
  <tr>
<?php $calendar = $calendars[$calendar_id]; if (is_array($calendar)): ?>
   <td><?php echo htmlspecialchars($calendar['name']) ?></td>
   <td><?php echo _("Remote") ?></td>
   <td><?php $url = $display_url_base->add('display_cal', 'remote_' . $calendar['url']); echo $url->link(array('title' => _("Click or copy this URL to display this calendar"), 'target' => '_blank')) . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php $url = new Horde_Url($calendar['url'], false); echo $url->link(array('title' => _("Click or copy this URL to display this calendar"), 'target' => '_blank')) . htmlspecialchars(shorten_url($calendar['url'])) . '</a>' ?></td>
   <td><?php echo $remote_edit_url_base->add('url', $calendar['url'])->link(array('title' => _("Edit"))) . $edit_img . '</a>' ?></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td>&nbsp;</td>
<?php endif; ?>
   <td><?php echo $remote_unsubscribe_url_base->add('url', $calendar['url'])->link(array('title' => _("Unsubscribe"))) . $delete_img . '</a>' ?></td>
<?php else: ?>
   <td><?php echo htmlspecialchars($calendar->get('name')) ?></td>
   <td><?php echo is_null($calendar->get('owner')) ? _("System") : _("Local") ?></td>
   <td><?php $url = $display_url_base->add('display_cal', $calendar->getName()); echo $url->link(array('title' => _("Click or copy this URL to display this calendar"))) . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php $url = new Horde_Url($subscribe_url_base . ($calendar->get('owner') ? $calendar->get('owner') : '-system-') . '/' . $calendar->getName() . '.ics'); echo $url->link(array('title' => _("Click or copy this URL to display this calendar"), 'target' => '_blank')) . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php echo $edit_url_base->add('c', $calendar->getName())->link(array('title' => _("Edit"))) . $edit_img . '</a>' ?></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo $perms_url_base->add('share', $calendar->getName())->link(array('title' => _("Change Permissions"), 'target' => '_blank', 'onclick' => Horde::popupJs($perms_url_base, array('params' => array('share' => $calendar->getName()), 'urlencode' => true)) . 'return false;')) . $perms_img . '</a>' ?></td>
<?php endif; ?>
   <td><?php echo $delete_url_base->add('c', $calendar->getName())->link(array('title' => _("Delete"))) . $delete_img . '</a>' ?></td>
<?php endif; ?>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
</div>
