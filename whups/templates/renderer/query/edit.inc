<tr><td class="rowOdd"><table cellspacing="3"><tr><td>
 <select name="qaction<?php echo $id ?>" onchange="document.<?php echo $formname ?>.submit()">
<?php foreach ($operations as $value => $name) { ?>
  <option value="<?php echo $value ?>"><?php echo $name ?></option>
<?php } ?>
 </select>
</td></tr></table></td></tr>
