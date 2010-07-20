<?php
/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    header('Location: ' . Horde::applicationUrl('', true));
    exit;
}

$title = _("Edit resources");

require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php'));
    exit;
}
$edit_url_base = Horde::applicationUrl('resources/edit.php');
$edit_img = Horde::img('edit.png', _("Edit"));
$resources = Kronolith::getDriver('Resource')->listResources(Horde_Perms::READ, array('type' => Kronolith_Resource::TYPE_SINGLE));
$display_url_base = Horde::applicationUrl('month.php', true, -1);
$delete_url_base = Horde::applicationUrl('resources/delete.php');
$delete_img = Horde::img('delete.png', _("Delete"));
?>
<script type="text/javascript">
function performAction(action, rid)
{
    document.resourceform.actionId.value = action;
    document.resourceform.actionValue.value = rid;
    document.resourceform.submit();
    return false;
}
</script>
<!-- Break out into template -->
<div id="page">

<h1 class="header">
 <?php echo _("Resources") ?>
</h1>
<?php if ($isAdmin = $registry->isAdmin()): ?>
 <form method="get" action="create.php">
  <?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Resource") ?>" />
  <a class="button" href="<?php echo Horde::applicationUrl('resources/groups') ?>"><?php echo _("Manage Resource Groups")?> </a>
 </form>
<?php endif ?>
<table summary="<?php echo _("Resource List") ?>" cellspacing="0" id="calendar-list" class="striped sortable">
 <thead>
  <tr>
   <th>&nbsp;</th>
   <th class="sortdown"><?php echo _("Name") ?></th>
   <th class="calendar-list-url nosort"><?php echo _("Display URL") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($resources as $resource): ?>
 <tr>
  <?php if ($isAdmin):?>
  <td>
   <?php echo $delete_url_base->add('c', $resource->getId())->link(array('title' => _("Delete"))) . $delete_img . '</a>' ?>
   <?php echo $edit_url_base->add('c', $resource->getId())->link(array('title' => _("Edit"))) . $edit_img . '</a>' ?>
  <?php else:?>
  <td>&nbsp;</td>
  <?php endif;?>
  <td><?php echo htmlspecialchars($resource->get('name')) ?></td>
  <td><?php $url = $display_url_base->add('display_cal', $resource->get('calendar'), false); echo $url->link(array('title' => _("Click or copy this URL to display this calendar"))) . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
 </tr>
<?php endforeach; ?>
</tbody>
</table>

<?php
/**
 * Show just the beginning and end of long URLs.
 */
function shorten_url($url, $separator = '...', $first_chunk_length = 35, $last_chunk_length = 15)
{
    $url_length = strlen($url);
    $max_length = $first_chunk_length + strlen($separator) + $last_chunk_length;

    if ($url_length > $max_length) {
        return substr_replace($url, $separator, $first_chunk_length, -$last_chunk_length);
    }

    return $url;
}
?>
</div>
