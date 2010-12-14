<h1 class="header">
 <?php echo _("Manage Feeds") ?>
</h1>

<?php if (!$prefs->isLocked('default_feed')): ?>
<div id="feed-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Feed") ?>" />
 </form>
</div>
<?php endif; ?>

<table summary="<?php echo _("Feed List") ?>" cellspacing="0" id="feed-list" class="striped sortable">
 <thead>
  <tr>
   <th class="feed-list-icon nosort" colspan="<?php echo empty($conf['share']['no_sharing']) ? 3 : 2 ?>">&nbsp;</th>
   <th class="sortdown"><?php echo _("Feed") ?></th>
   <th><?php echo _("Kind") ?></th>
   <th class="feed-list-url nosort"><?php echo _("Display URL") ?></th>
   <th class="feed-list-url nosort"><?php echo _("Subscription URL") ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_feeds) as feed_id): ?>
 <?php $feed = $feeds[feed_id] ?>
  <tr>
   <td><?php echo Horde::link(Horde_Util::addParameter($edit_url_base, 't', $feed->getName()), _("Edit")) . $edit_img . '</a>' ?></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo Horde::link(Horde_Util::addParameter($perms_url_base, 'share', $feed->getName()), _("Change Permissions"), '', '_blank', Horde::popupJs($perms_url_base, array('params' => array('share' => $feed->getName()), 'urlencode' => true)) . 'return false;') . $perms_img . '</a>' ?></td>
<?php endif; ?>
   <td><?php echo Horde::link(Horde_Util::addParameter($delete_url_base, 't', $feed->getName()), _("Delete")) . $delete_img . '</a>' ?></td>
   <td><?php echo htmlspecialchars($feed->get('name')) ?></td>
   <td><?php echo is_null($feed->get('owner')) ? _("System") : _("Local") ?></td>
   <td><?php $url = Horde_Util::addParameter($display_url_base, 'display_feed', $feed->getName(), false); echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php $url = $subscribe_url_base . ($feed->get('owner') ? $feed->get('owner') : '-system-') . '/' . $feed->getName() . '.ics'; echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
<?php endforeach; ?>
 </tbody>
</table>
