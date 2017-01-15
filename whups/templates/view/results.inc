<div class="header nowrap">
  <span class="nowrap rightAlign rightFloat subscribe"><?php echo isset($this->_params['extra']) ? $this->_params['extra'] : '&nbsp;' ?></span>
  <?php echo $this->_params['title'] . ' (' . count($this->_params['results']) . ')' ?>
</div>

<?php if (count($this->_params['results'])): ?>
<br class="spacer" />
<?php if ($GLOBALS['registry']->getAuth()): ?>
<form id="delete-form" method="post" action="<?php echo Horde::url('ticket/delete_multiple.php') ?>">
<input type="hidden" name="url" value="<?php echo Horde::selfUrl(true, true, true) ?>" />
<?php endif ?>
<table class="horde-table tickets sortable" id="sresults_<?php echo $this->_id ?>" width="100%">
<thead><tr class="nowrap">
<th class="nosort">
<?php if ($GLOBALS['registry']->getAuth()): ?>
<input type="checkbox" id="check-all" />
<?php endif ?>
</th>
<?php
foreach ($this->_params['values'] as $name => $value):
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
?>
<th id="<?php echo $value ?>"<?php echo $class ?>><?php echo $sortlink ?></th>
<?php endforeach ?>
</tr></thead>
<tbody>
<?php foreach ($this->_params['results'] as $info): ?>
  <tr>
    <td>
<?php if (Whups::hasPermission($info['queue'], 'queue', Horde_Perms::DELETE)): ?>
      <input type="checkbox" name="ticket[]" value="<?php echo $info['id'] ?>" />
<?php endif ?>
    </td>
<?php
$url = Whups::urlFor('ticket', $info['id']);
foreach ($this->_params['values'] as $name => $value):
    $thevalue = Whups::formatColumn($info, $value);
    $sortval = '';
    if ($value == 'timestamp' || $value == 'due' ||
        substr($value, 0, 5) == 'date_') {
        $sortval = isset($info[$value]) ? ' sortval="' . $info[$value] . '"' : '';
    }
?>
    <td<?php echo $sortval ?>><?php echo strlen($thevalue) ? $thevalue : '&nbsp;' ?></td>
<?php endforeach ?>
  </tr>
<?php endforeach ?>
</tbody>
</table>
<?php if ($GLOBALS['registry']->getAuth()): ?>

<div class="horde-form-buttons">
  <input type="submit" class="horde-delete" name="deletemulti" value="<?php echo _("Delete Selected") ?>" />
</div>
</form>

<div class="horde-form-buttons">
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
    <input type="submit" class="horde-default" value="<?php echo _("Save") ?>" />
  </form>
</div>
<?php endif ?>
<?php endif ?>
