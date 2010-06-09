<table cellspacing="5" width="100%">
 <tr>
  <td>&nbsp;</td>
  <td>
   <input type="submit" class="button" name="search" value="<?= _("Search") ?>" />
   <input type="reset" class="button" name="reset" value="<?= _("Reset to Defaults") ?>" />
  </td>
 </tr>
 <? if (count($this->addressBooks) > 1): ?>
 <tr>
  <td class="rightAlign"><strong><label for="source"><?= _("Address Book") ?></label></strong></td>
  <td class="leftAlign">
   <select id="source" name="source" onchange="directory_search.submit()">
    <? foreach ($this->addressBooks as $key => $entry): ?>
    <option<?= $key == $this->source ? ' selected="selected"' : '' ?> value="<?= $key ?>"><?= $this->h($entry['title']) ?></option>
    <? endforeach; ?>
   </select>
  </td>
 </tr>
<? endif; ?>
<? foreach ($this->map as $name => $v): ?>
<? if (substr($name, 0, 2) != '__'): ?>
 <tr>
  <td width="1%" class="nowrap rightAlign" ><strong><label for="<?= $name ?>"><?= $this->h($this->attributes[$name]['label']) ?></label></strong></td>
  <td class="leftAlign"><input type="text" size="30" id="<?= $name ?>" name="<?= $name ?>" value="<?= isset($this->criteria[$name]) ? $this->h($this->criteria[$name]) : '' ?>" /></td>
 </tr>
<? endif; ?>
<? endforeach; ?>
</table>
