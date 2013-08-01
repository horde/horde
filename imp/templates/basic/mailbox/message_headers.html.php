<table class="messageList">
 <tr class="horde-table-header">
  <th id="checkheader">
<?php if ($this->show_checkbox): ?>
   <label for="checkAll" class="hidden"><?php echo _("Check All/None") ?></label>
   <?php echo $this->checkBoxTag('checkAll', 1, false, array_merge(array('class' => 'checkbox'), $this->hordeAccessKeyAndTitle(_("Check _All/None"), false, true))) ?>
  </th>
<?php endif; ?>
<?php foreach ($this->headers as $v): ?>
  <th class="<?php echo $v['class'] ?>" id="<?php echo $v['id'] ?>">
   <?php echo $v['change_sort_link'] ?>
   <?php echo $v['change_sort_widget'] ?>
<?php if (!empty($v['altsort'])): ?>
   <span class="toggleSort">[<?php echo $v['altsort'] ?>]</span>
<?php endif; ?>
  </th>
<?php endforeach; ?>
 </tr>
