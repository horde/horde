<script type="text/javascript">
var PREFS_UPDATE_TIMEOUT;

function table_sortCallback(tableId, column, sortDown)
{
    if (typeof PREFS_UPDATE_TIMEOUT != "undefined") {
        window.clearTimeout(PREFS_UPDATE_TIMEOUT);
    }

    PREFS_UPDATE_TIMEOUT = window.setTimeout('doResort("' + column + '", "' + sortDown + '")', 500);
}

function doResort(column, sortDown)
{
    baseurl = '<?php echo addslashes(html_entity_decode($GLOBALS['session']->get('whups', 'last_search'))) ?>';
    try {
        new Ajax.Request(
            baseurl,
            {
                parameters: {
                    'sortby': encodeURIComponent(column),
                    'sortdir': encodeURIComponent(sortDown),
                    'isajax': true
                }
            });
    } catch (e) {}
}
</script>

<div class="header nowrap">
  <span class="nowrap rightAlign rightFloat subscribe"><?php echo isset($this->_params['extra']) ? $this->_params['extra'] : '&nbsp;' ?></span>
  <?php echo $this->_params['title'] . ' (' . count($this->_params['results']) . ')' ?>
</div>
<?php if (count($this->_params['results'])): ?>
<!-- Results Begin -->
<table class="tickets striped sortable" id="sresults_<?php echo $this->_id ?>" width="100%" cellspacing="0">
<?php
     echo '<thead><tr class="item nowrap leftAlign">';
     foreach ($this->_params['values'] as $name => $value) {
         if ($value == 'user_id_requester') {
             $value = 'requester_formatted';
         }
         if ($sortby == $value) {
             $class = ' class="' . $sortdirclass . '"';
             $revurl = Horde::url($this->_params['url'])->add('sortdir', ($sortdir ? 0 : 1));
             $sortlink = Horde::link($revurl, sprintf(_("Sort by %s"), $name), 'sortlink') . $name . '</a> ';
         } else {
             $class = '';
             $url = Horde::url($this->_params['url'])->add('sortby', $value);
             $sortlink = Horde::link($url, sprintf(_("Sort by %s"), $name), 'sortlink') . $name . '</a> ';
         }

         echo '<th id="' . $value . '"' . $class . '>' . $sortlink . '</th>';
     }
     echo '</tr></thead><tbody>';

     foreach ($this->_params['results'] as $info) {
         echo '<tr>';

         $url = Whups::urlFor('ticket', $info['id']);

         foreach ($this->_params['values'] as $name => $value) {
             $thevalue = Whups::formatColumn($info, $value);
             $sortval = '';
             if ($value == 'timestamp' || $value == 'due' ||
                 substr($value, 0, 5) == 'date_') {
                 $sortval = (strlen($thevalue) ? ' sortval="' . $thevalue . '"' : '');
             }

             echo '<td' . $sortval . '>' . (strlen($thevalue) ? $thevalue : '&nbsp;') . '</td>';
         }

         echo '</tr>';
     }
?>
</tbody>
</table>
<?php if ($GLOBALS['registry']->getAuth()): ?>
<table width="100%" cellspacing="0">
 <tr>
  <td class="smallheader nowrap">
    <form action="<?php echo $registry->downloadUrl('report.html') ?>" method="post" name="datagen">
      <?php Horde_Util::pformInput() ?>
      <input type="hidden" name="ids" value="<?php echo implode(',', $ids) ?>" />
      <input type="hidden" name="actionID" value="report" />
      <?php echo _("Download these results as: ") ?>
      <select name="template">
        <?php foreach (Whups::listTemplates('searchresults') as $name => $label): ?>
          <option value="<?php echo $name ?>"><?php echo htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="submit" class="button" value="<?php echo _("Save") ?>" />
    </form>
  </td>
 </tr>
</table>
<?php endif; endif; ?>
<!-- Results End -->
<br class="spacer" />
