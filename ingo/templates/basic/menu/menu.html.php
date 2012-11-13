<?php if ($this->options): ?>
<form action="<?php echo $this->formurl ?>" method="post" name="rulesetsmenu">
 <div style="float:right">
  <label for="ruleset" class="hidden"><?php echo _("Select ruleset to display") ?>:</label>
  <select id="ruleset" name="ruleset" onchange="document.rulesetsmenu.submit(); return false;">
   <option value=""><?php echo _("Select ruleset to display") ?>:</option>
<?php foreach ($this->options as $v): ?>
   <?php echo $this->optionTag($v['val'], $v['name'], $v['selected']) ?>
<?php endforeach; ?>
  </select>
 </div>
</form>
<?php endif; ?>
