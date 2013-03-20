<h1 class="header"><?php echo $this->header /* Unescaped output */ ?></h1>

<div class="horde-buttonbar">
  <ul>
    <li class="horde-icon"><?php echo Horde::widget(array('url' => Horde::url('delete.php'), 'title' => _("_Delete"), 'class' => 'skeleton-delete')) ?></li>
    <li><?php echo Horde::url('foo.php')->link() . _("Foo") . '</a>' ?></li>
  </ul>
</div>

<table class="horde-table sortable">
  <thead>
    <tr>
      <th width="10%"><?php echo _("Column 1") ?></th>
      <th class="horde-split-left"><?php echo _("Column 2") ?></t4>
    </tr>
  </thead>
  <tbody>
<?php foreach ($this->list as $row): ?>
    <tr>
      <td><?php echo ($row[0]) ?></td>
      <td><?php echo ($row[1]) ?></td>
    </tr>
<?php endforeach ?>
  </tbody>
</table>

<p class="horde-form-buttons">
  <input type="submit" class="horde-default" />
  <a class="horde-button" href="<?php echo Horde::url('foo.php') ?>"><?php echo _("Foo") ?></a>
  <input type="button" class="horde-delete" value="<?php echo _("Delete") ?>" />
  <input type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
</p>

<p class="horde-content">
  <?php echo $this->h($this->content) /* Escaped output */ ?>
</p>
