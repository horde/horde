<hr />

<div><?php echo _("Menu") ?></div>

<ul class="mimpMenu">
<?php foreach ($this->menu as $val): ?>
 <li><a href="<?php echo $val[1] ?>"><?php echo $this->h($val[0]) ?></a></li>
<?php endforeach; ?>
</ul>
