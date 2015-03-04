<h2 class="smallheader rowOdd"><?php echo _("Script name") ?>: <?php echo $this->h($script['name']) ?></h2>
<pre>
<?php foreach ($script['lines'] as $i => $line): ?>
<?php printf('%' . $script['width'] . "d: %s\n", $i + 1, $this->h($line)) ?>
<?php endforeach; ?>
</pre>
