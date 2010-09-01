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
    baseurl = '<?php echo addslashes(html_entity_decode($_SESSION['whups']['last_search'])) ?>';
    try {
        new Ajax.Request(baseurl, { parameters: { 'sortby': encodeURIComponent(column), sortdir: encodeURIComponent(sortDown) } });
    } catch (e) {}
}
</script>

<table width="100%" cellspacing="0">
 <tr>
  <td class="header nowrap"><?php echo $this->_params['title'] . ' (' . count($this->_params['results']) . ')' ?></td>
  <td class="header nowrap rightAlign"><?php echo isset($this->_params['extra']) ? $this->_params['extra'] : '&nbsp;' ?></td>
 </tr>
</table>
<?php if (count($this->_params['results'])): ?>
<!-- Results Begin -->
<table class="tickets striped sortable" id="sresults_<?php echo $this->_id ?>" width="100%" cellspacing="0">
<?php
     echo '<thead><tr class="item nowrap leftAlign">';
     foreach ($this->_params['values'] as $name => $value) {
         if ($sortby == $value) {
             $class = ' class="' . $sortdirclass . '"';
             $revurl = Horde_Util::addParameter($this->_params['url'], 'sortdir', ($sortdir ? 0 : 1));
             $sortlink = Horde::link($revurl, sprintf(_("Sort by %s"), $name), 'sortlink') . $name . '</a> ';
         } else {
             $class = '';
             $url = Horde_Util::addParameter($this->_params['url'], 'sortby', $value);
             $sortlink = Horde::link($url, sprintf(_("Sort by %s"), $name), 'sortlink') . $name . '</a> ';
         }

         echo '<th id="' . $value . '"' . $class . '>' . $sortlink . '</th>';
     }
     echo '</tr></thead><tbody>';

     foreach ($this->_params['results'] as $info) {
         echo '<tr>';

         $url = Whups::urlFor('ticket', $info['id']);

         foreach ($this->_params['values'] as $name => $value) {
             $thevalue = isset($info[$value]) ? $info[$value] : '';

             if ($value == 'timestamp' || substr($value, 0, 5) == 'date_') {
                 $sortval = (strlen($thevalue) ? ' sortval="' . $thevalue . '"' : '');
                 $thevalue = Horde_Form_Type_date::getFormattedTime($thevalue, $prefs->getValue('report_time_format'), false);
             } elseif ($value == 'user_id_requester') {
                 $thevalue = Whups::formatUser($thevalue, false, true, true);
             } elseif ($value == 'id' || $value == 'summary') {
                 $sortval = '';
                 $thevalue = Horde::link($url) . '<strong>' . htmlspecialchars($thevalue) . '</strong></a>';
             } elseif ($value == 'owners') {
                 $sortval = '';
                 if (!empty($info['owners'])) {
                     $owners = array();
                     foreach ($thevalue as $owner) {
                         $owners[] = Whups::formatUser($owner, false);
                     }
                     $thevalue = implode(', ', $owners);
                 }
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
    <form action="<?php echo Horde::downloadUrl('report.html', null, Horde::url('data.php')) ?>" method="post" name="datagen">
      <?php Horde_Util::pformInput() ?>
      <input type="hidden" name="ids" value="<?php echo implode(',', $ids) ?>" />
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