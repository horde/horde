<div id="quickAddInfoPanel" class="quickAddInfoPanel" style="display:none;">
 <h2><?php echo _("Quick Task Creation") ?></h2>
 <form method="post" action="quick.php">
  <p><?php echo _("Enter one task per line. Create child tasks by indenting them below their parent task. Include due dates like \"laundry tomorrow\" or \"get dry cleaning next Thursday\". Tags may be added by prefixing them with the \"#\" character.") ?></p>
  <textarea name="quickText" id="quickText"></textarea>
  <p>
   <input type="submit" class="horde-default" value="<?php echo _("Create") ?>" />
   <input type="button" class="horde-cancel" onclick="RedBox.close()" value="<?php echo _("Cancel") ?>" />
  </p>
 </form>
</div>

<div class="horde-subnavi-split"></div>

<h3>
  <?php if ($this->newShares): ?>
  <a href="<?php echo Horde::url('tasklists/create.php') ?>" class="horde-add" title="<?php echo _("New Task List") ?>">+</a>
  <?php endif; ?>
  <span class="horde-collapse" title="<?php echo _("Collapse") ?>"><?php echo _("My Task Lists") ?></span>
</h3>

<div class="horde-resources">
<?php foreach ($this->my as $list): ?>
  <div class="<?php echo $list['class'] ?>" style="<?php echo $list['style'] ?>">
    <?php echo $list['edit'] ?>
    <?php echo $list['link'] ?>
  </div>
<?php endforeach ?>
</div>

<div class="horde-sidebar-split"></div>

<div>
  <h3>
    <span class="horde-expand" title="<?php echo _("Expand") ?>"><?php echo _("Shared Task Lists") ?></span>
  </h3>

  <div style="display:none">
<?php if (count($this->shared)): ?>
    <div class="horde-resources">
<?php foreach ($this->shared as $list): ?>
      <div class="<?php echo $list['class'] ?>" style="<?php echo $list['style'] ?>">
        <?php echo $list['edit'] ?>
        <?php echo $list['link'] ?>
      </div>
<?php endforeach ?>
    </div>
<?php else: ?>
    <div class="horde-info"><?php echo _("No items to display") ?></div>
<?php endif ?>
  </div>
</div>
