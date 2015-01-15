<pre>
<?php foreach ($script['lines'] as $i => $line): ?>
<?php printf('%' . $script['width'] . "d: %s\n", $i + 1, $this->h($line)) ?>
<?php endforeach; ?>
</pre>
