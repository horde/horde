<div class="horde-buttonbar">
 <ul class="rightFloat">
  <li class="horde-nobutton nowrap">
   <span class="imp-navbar">
    <?php echo $this->back_to ?>
<?php if ($this->prev): ?>
    <?php echo $this->prev ?><span class="iconImg <?php echo $this->prev_img ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->prev_img ?>"></span>
<?php endif; ?>
<?php if ($this->next): ?>
    <?php echo $this->next ?><span class="iconImg <?php echo $this->next_img ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->next_img ?>"></span>
<?php endif; ?>
   </span>
  </li>
 </ul>

<?php if (!$this->readonly): ?>
 <ul>
<?php if ($this->flaglist_set): ?>
  <li>
   <input type="hidden" name="mailbox" value="<?php echo $this->mailbox ?>" />
   <label for="flag<?php echo $this->id ?>" class="hidden"><?php echo _("Mark Message") ?></label>
   <select id="flag<?php echo $this->id ?>">
    <option value="" selected="selected"><?php echo _("Mark Message") ?></option>
    <option value="" disabled="disabled">- - - - - - - -</option>
    <option class="actionsSelectSection" value=""><?php echo _("Mark as:") ?></option>
<?php foreach ($this->flaglist_set as $v): ?>
    <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
    <option value="" disabled="disabled">- - - - - - - -</option>
    <option class="actionsSelectSection" value=""><?php echo _("Unmark as:") ?></option>
<?php foreach ($this->flaglist_unset as $v): ?>
    <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
   </select>
  </li>
<?php endif; ?>
<?php if ($this->move): ?>
  <li>
   <?php echo $this->move ?> | <?php echo $this->copy ?>
   <label for="target<?php echo $this->id ?>" class="hidden"><?php echo _("Target Mailbox") ?></label>
   <select id="target<?php echo $this->id ?>" name="target<?php echo $this->id ?>">
    <?php echo $this->options ?>
   </select>
  </li>
<?php endif; ?>
 </ul>
<?php endif; ?>
</div>

<?php if ($this->isbottom): ?>
</form>
<?php endif; ?>
