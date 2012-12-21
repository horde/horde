<script type="text/javascript">
function table_sortCallback(tableId, column, sortDown)
{
    new Ajax.Request('<?php echo $GLOBALS['registry']->getServiceLink('ajax', 'trean')->url ?>setPrefValue', { parameters: { pref: 'sortby', value: column.substring(2) } });
    new Ajax.Request('<?php echo $GLOBALS['registry']->getServiceLink('ajax', 'trean')->url ?>setPrefValue', { parameters: { pref: 'sortdir', value: sortDown } });
}
</script>

<table class="horde-table sortable" cellspacing="0">
<thead>
 <tr>
  <th id="s_title" class="horde-split-left<?php if ($this->sortby == 'title') echo ' ' . $this->sortdirclass ?>"><?php echo _("Title") ?></th>
  <th id="s_dt"<?php if ($this->sortby == 'dt') echo ' class="' . $this->sortdirclass . '"' ?>><?php echo _("Added")?></th>
  <th id="s_clicks" class="horde-split-left<?php if ($this->sortby == 'clicks') echo ' ' . $this->sortdirclass ?>" width="1%"><?php echo _("Clicks") ?></th>
  <th width="10%" class="horde-split-left nosort"></th>
 </tr>
</thead>
<tbody id="BookmarkList-body">
 <?php foreach ($this->bookmarks as $bookmark): ?>
 <tr>
  <td>
   <div class="trean-bookmarks-title">
    <div class="trean-favicon-container">
     <?php echo Horde::img(Trean::getFavicon($bookmark), '', array('class' => 'trean-favicon')) ?>
    </div>
    <?php
if ($bookmark->http_status == 'error') {
    echo Horde::img('http/error.png');
} elseif ($bookmark->http_status) {
    echo Horde::img('http/' . (int)substr($bookmark->http_status, 0, 1) . 'xx.png');
}
    ?>
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
  <td class="trean-bookmarks-date">
   <?php if ($bookmark->dt) { $dt = new Horde_Date($bookmark->dt); echo $dt->strftime($GLOBALS['prefs']->getValue('date_format')); } ?>
  </td>
  <td class="trean-bookmarks-clicks">
   <?php echo $bookmark->clicks ?>
  </td>
  <td class="trean-bookmarks-actions">
   <a href="<?php echo Horde::url('edit.php')->add('bookmark', (int)$bookmark->id) ?>"><?php echo Horde::img('edit.png', _("Edit")) ?></a>
   <form action="<?php echo Horde::url('b/delete') ?>" method="post">
    <input type="hidden" name="bookmark" value="<?php echo (int)$bookmark->id ?>" />
    <input type="hidden" name="url" value="<?php echo $this->h(Horde::selfUrl(true)) ?>" />
    <input type="image" src="<?php echo Horde_Themes::img('delete.png') ?>" />
   </form>
  </td>
 </tr>
 <?php endforeach ?>
</tbody>
</table>
