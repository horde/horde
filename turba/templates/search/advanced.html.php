<table cellspacing="5" width="100%">
 <tr>
  <td>&nbsp;</td>
  <td>
   <input type="submit" class="button" name="search" value="<?php echo _("Search") ?>" />
   <input type="reset" class="button" name="reset" value="<?php echo _("Reset to Defaults") ?>" />
  </td>
 </tr>
 <? if (count($this->addressBooks) > 1): ?>
 <tr>
  <td class="rightAlign"><strong><label for="source"><?php echo _("Address Book") ?></label></strong></td>
  <td class="leftAlign">
   <select id="source" name="source" onchange="directory_search.submit()">
    <? foreach ($this->addressBooks as $key => $entry): ?>
    <option<?php echo $key == $this->source ? ' selected="selected"' : '' ?> value="<?php echo $key ?>"><?php echo $this->h($entry['title']) ?></option>
    <? endforeach; ?>
   </select>
  </td>
 </tr>
<? endif; ?>
<? foreach ($this->map as $name => $v): ?>
<? if (substr($name, 0, 2) != '__'): ?>
 <tr>
  <td width="1%" class="nowrap rightAlign" ><strong><label for="<?php echo $name ?>"><?php echo $this->h($this->attributes[$name]['label']) ?></label></strong></td>
  <td class="leftAlign"><input type="text" size="30" id="<?php echo $name ?>" name="<?php echo $name ?>" value="<?php echo isset($this->criteria[$name]) ? $this->h($this->criteria[$name]) : '' ?>" /></td>
 </tr>
<? endif; ?>
<? endforeach; ?>
</table>
