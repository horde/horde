<script type="text/javascript">
var PREFS_UPDATE_TIMEOUT;
function table_sortCallback(tableId, column, sortDown)
{
    if (typeof PREFS_UPDATE_TIMEOUT != "undefined") {
        window.clearTimeout(PREFS_UPDATE_TIMEOUT);
    }
    PREFS_UPDATE_TIMEOUT = window.setTimeout('doPrefsUpdate("' + column + '", "' + sortDown + '")', 300);
}

function doPrefsUpdate(column, sortDown)
{
    baseurl = '<?php echo Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/prefs/', true) ?>';
    try {
        new Ajax.Request(baseurl, { parameters: { app: 'trean', pref: 'sortby', value: encodeURIComponent(column.substring(2)) } });
        new Ajax.Request(baseurl, { parameters: { app: 'trean', pref: 'sortdir', value: encodeURIComponent(sortDown) } });
    } catch (e) {}
}
</script>

<table class="striped sortable" cellspacing="0" id="BookmarkList">
<thead>
 <tr>
  <th width="1%" class="nosort"><input title="<?php echo _("Select All/Select None") ?>" type="checkbox" class="checkbox" onclick="$$('#BookmarkList-body input[type=checkbox]').each((function(c) { c.checked = this.checked; }).bind(this));" /></th>
  <th width="1%" class="nosort"></th>
  <th id="s_title"<?php if ($this->sortby == 'title') echo ' class="' . $this->sortdirclass . '"' ?>><?php echo _("Title") ?></th>
  <?php if ($this->showFolder): ?><th><?php echo _("Folder") ?></th><?php endif; ?>
  <th id="s_rating"<?php if ($this->sortby == 'rating') echo ' class="' . $this->sortdirclass . '"' ?> width="1%"><?php echo _("Rating") ?></th>
  <th id="s_clicks"<?php if ($this->sortby == 'clicks') echo ' class="' . $this->sortdirclass . '"' ?> width="1%"><?php echo _("Clicks") ?></th>
 </tr>
</thead>
<tbody id="BookmarkList-body">
<?php
// List bookmarks.
foreach ($this->bookmarks as $bookmark) {
    $bookmark_url = Horde_Util::addParameter($this->redirectUrl, 'b', $bookmark->id);
    if ($bookmark->http_status == 'error') {
        $status = 'error.png';
    } elseif ($bookmark->http_status == '') {
        $status = '';
    } else {
        $status = (int)substr($bookmark->http_status, 0, 1) . 'xx.png';
    }
?>
 <tr>
  <td>
   <input type="checkbox" class="checkbox" name="bookmarks[]" value="<?php echo $bookmark->id ?>" />
  </td>
  <td class="nowrap">
   <?php echo Horde::img(Trean::getFavicon($bookmark), '', 'class="favicon"', '') ?>
   <?php if ($status) echo Horde::img('http/' . $status) ?>
  </td>
  <td>
   <div class="bl-title">
    <?php echo Horde::link($bookmark_url, '', '', $this->target) . htmlspecialchars($bookmark->title ? $bookmark->title : $bookmark->url) ?></a>
    <small> &#8230; <?php echo htmlspecialchars($bookmark->url) . ' &#8230; ' . htmlspecialchars(Horde_String::substr($bookmark->description, 0, 200)) ?></small>
   </div>
  </td>
  <td sortval="<?php echo $bookmark->rating ?>" class="rating">
   <?php echo star_rating_helper($bookmark) ?>
  </td>
  <td class="bl-clicks">
   <?php echo $bookmark->clicks ?>
  </td>
 </tr>
<?php } ?>
</tbody>
</table>
