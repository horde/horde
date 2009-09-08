<?php
/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
require_once dirname(__FILE__) . '/lib/base.php';

$title = _("Edit resources");

$resources = array();
$resources = Kronolith::listResources();
$display_url_base = Horde::applicationUrl('month.php', true, -1);


require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';

?>
<!-- Break out into template -->
<div id="page">

<h1 class="header">
 <?php echo _("Resources") ?>
</h1>
<?php if (Horde_Auth::isAdmin()): ?>
 <form method="get" action="createresource.php">
  <?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Resource") ?>" />
 </form>
<?php endif; ?>
<table summary="<?php echo _("Resource List") ?>" cellspacing="0" id="calendar-list" class="striped sortable">
 <thead>
  <tr>
   <th class="sortdown"><?php echo _("Name") ?></th>
   <th class="calendar-list-url nosort"><?php echo _("Display URL") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($resources as $resource): ?>
 <tr>
  <td><?php echo htmlspecialchars($resource->name) ?></td>
  <td><?php $url = Horde_Util::addParameter($display_url_base, 'display_cal', $resource->calendar, false); echo Horde::link($url, _("Click or copy this URL to display this calendar"), '', '_blank') . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
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
var_dump($resources);
/* Test creating a new resource */
//$new = array('name' => _("Another Big Meeting Room"),
//             'category' => 'conference rooms');
//
//$resource = new Kronolith_Resource_Single($new);
//$results = Kronolith::addResource($resource);
//var_dump($results);

/* Test adding resource to event */
$resource = Kronolith::getDriver('Resource')->getResource(9);

/* Any driver/event */
//$driver = Kronolith::getDriver('Sql');
//$event = $driver->getByUID('20090904121938.17551lvwtt52y728@localhost');
//$event->addResource($resource, Kronolith::RESPONSE_NONE);
//$event->save();


//
////var_dump($resource->getFreeBusy(null, null, true));
//
/* Test listing resources */
//var_dump(Kronolith::listResources());
?>
</div>