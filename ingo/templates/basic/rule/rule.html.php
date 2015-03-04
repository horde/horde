<form method="post" id="rule" name="rule" action="<?php echo $this->formurl ?>">
 <?php echo $this->hiddenFieldTag('actionID') ?>
 <?php echo $this->hiddenFieldTag('conditionnumber', -1) ?>
<?php if (!is_null($this->edit)): ?>
 <?php echo $this->hiddenFieldTag('edit', $this->edit) ?>
<?php endif; ?>

 <div class="header">
  <?php echo _("Filter Rule") ?>
<?php if ($this->rule->disable): ?>
  [<span class="horde-form-error"><?php echo _("Disabled") ?></span>]
<?php endif; ?>
  <?php echo $this->hordeHelp('ingo', 'rule') ?>
 </div>

 <div class="control">
  <?php echo $this->hordeLabel('name', _("Rule Name:")) ?>
  <input class="input" id="name" name="name" size="50" value="<?php echo $this->h($this->rule->name) ?>" />
  <?php echo $this->hordeHelp('ingo', 'rule-name') ?>
 </div>

 <div class="ruleDiv">
  <em><?php echo _("For an incoming message that matches:") ?></em>
  <?php echo $this->hordeHelp('ingo', 'rule-match') ?>

  <div class="ruleCondition">
   <span>
    <?php echo $this->radioButtonTag('combine', Ingo_Rule_User::COMBINE_ALL, $this->rule->combine == Ingo_Rule_User::COMBINE_ALL, array('id' => 'all')) ?>
    <?php echo $this->hordeLabel('all', _("ALL of the following")) ?>
   </span>
   <span>
    <?php echo $this->radioButtonTag('combine', Ingo_Rule_User::COMBINE_ANY, $this->rule->combine == Ingo_Rule_User::COMBINE_ANY, array('id' => 'any')) ?>
    <?php echo $this->hordeLabel('any', _("ANY of the following")) ?>
   </span>
  </div>

  <div class="ruleMatch">
<?php foreach ($this->filter as $f): ?>
<?php if ($f['cond_num'] > 0): ?>
   <div class="ruleMatchCondition">
    <?php echo ($this->rule->combine == Ingo_Rule_User::COMBINE_ALL) ? _("and") : _("or") ?>
   </div>
<?php endif; ?>
   <div class="ruleMatchRow">
    <div class="ruleMatchRowRules">
     <div>
      <label for="field_<?php echo $f['cond_num'] ?>" class="hidden"><?php echo _("Field") ?></label>
      <select id="field_<?php echo $f['cond_num'] ?>" name="field[<?php echo $f['cond_num'] ?>]">
<?php if ($f['lastfield']): ?>
       <option value=""><?php echo _("Select a field") ?></option>
       <option disabled="disabled" value="">- - - - - - - - - -</option>
<?php endif; ?>
<?php foreach ($this->fields as $k => $v): ?>
<?php if (in_array($v['type'], $this->avail_types)): ?>
       <?php echo $this->optionTag($k, $this->h($v['label']), $f['field'] == $k) ?>
<?php endif; ?>
<?php endforeach; ?>
<?php if (count($this->special)): ?>
       <option disabled="disabled" value="">- - - - - - - - - -</option>
<?php foreach ($this->special as $v): ?>
       <?php echo $this->optionTag($v, $this->h($v), $f['field'] == $v) ?>
<?php endforeach; ?>
<?php endif ?>
<?php if ($this->userheader): ?>
       <option disabled="disabled" value="">- - - - - - - - - -</option>
      <?php echo $this->optionTag(Ingo::USER_HEADER, _("Self-Defined Header"), isset($f['userheader'])) ?>
<?php endif; ?>
      </select>
<?php if (isset($f['userheader'])): ?>
      <label for="userheader_<?php echo $f['cond_num'] ?>" class="hidden"><?php echo _("User header") ?></label>
      <input id="userheader_<?php echo $f['cond_num'] ?>" name="userheader[<?php echo $f['cond_num'] ?>]" value="<?php echo $this->h($f['userheader']) ?>" />
<?php endif; ?>
     </div>
<?php if (!$f['lastfield']): ?>
     <div>
      <label for="match_<?php echo $f['cond_num'] ?>" class="hidden"><?php echo _("Match type") ?></label>
      <select id="match_<?php echo $f['cond_num'] ?>" name="match[<?php echo $f['cond_num'] ?>]">
<?php if (empty($f['matchtest'])): ?>
       <option disabled="disabled" value="">- - - - - - - - - -</option>
<?php else: ?>
<?php foreach ($f['matchtest'] as $v): ?>
       <?php echo $this->optionTag($v['value'], $this->h($v['label']), $v['selected']) ?>
<?php endforeach; ?>
<?php endif; ?>
      </select>

<?php if (isset($f['match_value'])): ?>
      <label for="value_<?php echo $f['cond_num'] ?>" class="hidden">Value</label>
      <input id="value_<?php echo $f['cond_num'] ?>" name="value[<?php echo $f['cond_num'] ?>]" size="40" value="<?php echo $this->h($f['match_value']) ?>" />
<?php endif; ?>
<?php if (isset($f['case_sensitive'])): ?>
      <?php echo $this->checkBoxTag('case[' . $f['cond_num'] . ']', 1, (bool)$f['case_sensitive'], array('class' => 'caseSensitive', 'id' => 'case_' . $f['cond_num'])) ?>
      <?php echo $this->hordeLabel('case_' . $f['cond_num'], _("Case Sensitive")) ?>
<?php endif; ?>
     </div>
<?php endif; ?>
    </div>
<?php if (!$f['lastfield']): ?>
    <div class="ruleMatchRowDelete">
     <?php echo Horde::link('javascript:IngoRule.delete_condition(' . intval($f['cond_num']) . ');', _("Delete Condition")) . $this->hordeImage('delete.png', _("Delete Condition")) ?></a>
    </div>
<?php endif; ?>
   </div>
<?php endforeach; ?>
  </div>
 </div>

 <div class="ruleDiv">
  <em><?php echo $this->hordeLabel('action', _("Do this:")) ?></em>
  <?php echo $this->hordeHelp('ingo', 'rule-action') ?>

  <div class="ruleAction">
   <select id="action" name="action">
<?php foreach ($this->actions as $v): ?>
    <?php echo $this->optionTag($v['value'], $this->h($v['label']), $v['selected']) ?>
<?php endforeach; ?>
   </select>
<?php if ($this->actionvaluelabel): ?>
   <label for="actionvalue" class="hidden"><?php echo $this->actionvaluelabel ?></label>
   <?php echo $this->actionvalue ?>
<?php endif; ?>
  </div>
 </div>

<?php if ($this->flags): ?>
 <div class="ruleDiv ruleMark">
  <em><?php echo _("Mark message as:") ?></em>
  <?php echo $this->hordeHelp('ingo', 'rule-mark') ?>

  <ul>
   <li>
    <?php echo $this->checkBoxTag('flags[]', Ingo_Rule_User::FLAG_SEEN, (bool)(Ingo_Rule_User::FLAG_SEEN & $this->rule->flags), array('id' => 'seen')) ?>
    <?php echo $this->hordeLabel('seen', _("Seen")) ?>
   </li>
   <li>
    <?php echo $this->checkBoxTag('flags[]', Ingo_Rule_User::FLAG_FLAGGED, (bool)(Ingo_Rule_User::FLAG_FLAGGED & $this->rule->flags), array('id' => 'flagged')) ?>
    <?php echo $this->hordeLabel('flagged', _("Flagged")) ?>
   </li>
   <li>
    <?php echo $this->checkBoxTag('flags[]', Ingo_Rule_User::FLAG_ANSWERED, (bool)(Ingo_Rule_User::FLAG_ANSWERED & $this->rule->flags), array('id' => 'answered')) ?>
    <?php echo $this->hordeLabel('answered', _("Answered")) ?>
   </li>
   <li>
    <?php echo $this->checkBoxTag('flags[]', Ingo_Rule_User::FLAG_DELETED, (bool)(Ingo_Rule_User::FLAG_DELETED & $this->rule->flags), array('id' => 'deleted')) ?>
    <?php echo $this->hordeLabel('deleted', _("Deleted")) ?>
   </li>
  </ul>
 </div>
<?php endif; ?>

<?php if ($this->stop): ?>
 <div class="ruleDiv ruleStopChecking">
  <?php echo $this->checkBoxTag('stop', '1', $this->rule->stop) ?>
  <?php echo $this->hordeLabel('stop', _("Stop checking if this rule matches?")) ?>
  <?php echo $this->hordeHelp('ingo', 'rule-stop') ?>
 </div>
<?php endif; ?>

 <div class="horde-form-buttons">
  <input class="horde-default" id="rule_save" type="button" value="<?php echo _("Save") ?>" />
  <input type="button" id="rule_cancel" value="<?php echo _("Return to Filters List") ?>" />
 </div>
</form>
