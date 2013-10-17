<hr />

<div><?php echo _("Menu") ?></div>

<ol class="mimpMenu">
<?php $i = 0; ?>
<?php foreach ($this->menu as $val): ?>
 <li><a accesskey="<?php echo ++$i ?>" href="<?php echo $val[1] ?>"><?php echo $this->h($val[0]) ?></a></li>
<?php endforeach; ?>
</ol>
