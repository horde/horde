<h1 class="header">
 <?php echo _("Manage Feeds") ?>
</h1>

<?php if (!$prefs->isLocked('default_feed')): ?>
<div id="feed-list-buttons">
 <form method="get" action="channels/edit.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Feed") ?>" />
 </form>
</div>
<?php endif; ?>

<table summary="<?php echo _("Feed List") ?>" cellspacing="0" id="feed-list" class="striped sortable">
 <thead>
  <tr>
   <th class="feed-list-icon nosort" colspan=<?php $conf['share']['no_sharing'] ? 3:4 ?>>&nbsp;</th>
   <th class="sortdown"><?php echo _("Feed") ?></th>
   <th><?php echo _("Kind") ?></th>
   <th class="feed-list nosort"><?php echo _("Display URL") ?></th>
   <th class="feed-list nosort"><?php echo _("Subscription URL") ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_feeds) as $feed_id): ?>
 <?php $feed = $feeds[$feed_id] ?>
  <tr>
   <td><?php echo Horde::link(Horde::url('stories/add'), _("Add Story")) . $add_img . '</a>' ?></td>
   <td><?php echo Horde::link(Horde::url('channels/' . $feed->getName() . '/edit'), _("Edit")) . $edit_img . '</a>' ?></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo Horde::link(Horde_Util::addParameter($perms_url_base, 'share', $feed->getName()), _("Change Permissions"), '', '_blank', Horde::popupJs($perms_url_base, array('params' => array('share' => $feed->getName()), 'urlencode' => true)) . 'return false;') . $perms_img . '</a>' ?></td>
<?php endif; ?>
   <td><?php echo Horde::link(Horde::url($feed->getName()), _("Delete")) . $delete_img . '</a>' ?></td>
   <td><?php echo htmlspecialchars($feed->get('name')) ?></td>
   <td><?php echo is_null($feed->get('owner')) ? _("System") : _("User") ?></td>
   <td><?php $url = Horde::url($feed->getName()); echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars($url) . '</a>' ?></td>
   <td><?php $url = Horde::url('/' . $feed->getName() . '/rss'); echo Horde::link($url, _("Click or copy this URL to display this feed"), '', '_blank') . htmlspecialchars($url) . '</a>' ?></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
