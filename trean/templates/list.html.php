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

<div id="bookmarkList">
<table class="striped sortable" cellspacing="0" id="BookmarkList">
<thead>
 <tr>
  <th id="s_title"<?php if ($this->sortby == 'title') echo ' class="' . $this->sortdirclass . '"' ?>><?php echo _("Title") ?></th>
  <th id="s_clicks"<?php if ($this->sortby == 'clicks') echo ' class="' . $this->sortdirclass . '"' ?> width="1%"><?php echo _("Clicks") ?></th>
  <th width="10%" class="nosort"></th>
 </tr>
</thead>
<tbody id="BookmarkList-body">
 <?php foreach ($this->bookmarks as $bookmark): ?>
 <tr>
  <td>
   <div class="bl-title">
    <?php echo Horde::img(Trean::getFavicon($bookmark), '', array('class' => 'favicon')) ?>
    <?php if ($bookmark->http_status == 'error'): ?>
    <?php echo Horde::img('http/error.png') ?>
    <?php elseif ($bookmark->http_status): ?>
    <?php echo Horde::img('http/' . (int)substr($bookmark->http_status, 0, 1) . 'xx.png') ?>
    <?php endif; ?>
    <?php echo $this->redirectUrl->add('b', $bookmark->id)->link(array('target' => $this->target)) . $this->h($bookmark->title ? $bookmark->title : $bookmark->url) ?></a>
    <small>
      <?php echo $this->h($bookmark->url) ?>
      <?php if (strlen($bookmark->description)): ?>
      &mdash;
      <?php echo $this->h(Horde_String::truncate($bookmark->description, 200)) ?>
      <?php endif ?>
    </small>
    <ul class="horde-tags">
     <?php foreach ($bookmark->tags as $tag): ?>
     <li><a href="<?php echo Horde::selfUrl()->add('tag', $tag) ?>"><?php echo $this->h($tag) ?></a></li>
     <?php endforeach ?>
    </ul>
   </div>
  </td>
  <td class="bl-clicks">
   <?php echo $bookmark->clicks ?>
  </td>
  <td class="bl-actions">
   <a href="<?php echo Horde::url('edit.php')->add('bookmark', (int)$bookmark->id) ?>"><?php echo Horde::img('edit.png', _("Edit")) ?></a>
   <form class="bl-delete" action="<?php echo Horde::url('b/delete') ?>" method="post">
    <input type="hidden" name="bookmark" value="<?php echo (int)$bookmark->id ?>">
    <input type="hidden" name="url" value="<?php echo $this->h(Horde::selfUrl()) ?>">
    <input type="submit" class="button" value="<?php echo _("Delete") ?>">
   </form>
  </td>
 </tr>
 <?php endforeach ?>
</tbody>
</table>
</div>
