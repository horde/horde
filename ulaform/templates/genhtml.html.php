<?php echo $this->inputform; ?>

<?php if (!empty($this->html)): ?>
<ul class="fixed striped" style="margin:8px">
<?php foreach ($this->html as $html): ?><li><?php echo $html; ?></li><?php endforeach; ?>
</ul>
<?php endif; ?>
