<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<h1 class="header"><?php echo $this->_arrays['forum']['forum_name']; ?></h1>
<div class="box"><?php echo $this->_arrays['forum']['forum_description']; ?></div>

<br class="spacer">

<?php if (!empty($this->banned)): ?>
<h1 class="header"><?php echo _('Banned'); ?></h1>
<ul>
<?php foreach ($this->banned as $k1 => $v1): ?>
    <li><?php if (isset($v1)) { echo is_array($v1) ? $k1 : $v1; } elseif (isset($this->banned)) { echo $this->banned; } ?></li>
<?php endforeach; ?>
</ul>
<br class="spacer">
<?php endif; ?>

<?php echo $this->formbox; ?>
