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
        <?php echo $list['link'] ?>
      </div>
<?php endforeach ?>
    </div>
<?php else: ?>
    <div class="horde-info"><?php echo _("No items to display") ?></div>
<?php endif ?>
  </div>
</div>
