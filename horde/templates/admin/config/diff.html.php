<?php if (empty($diffs)): ?>
<p class="notice"><?php echo _("No available configuration data to show differences for.") ?></p>
<?php else: ?>
<?php foreach ($diffs as $d): ?>
<h1 class="header" id="<?php echo $d['app'] ?>">
 <?php echo $d['app'] ?>
 <small>[ <?php echo $d['toggle_renderer'] ?> ]</small>
</h1>
<pre class="text"><?php echo $d['diff'] ?></pre>
<?php endforeach; ?>
<?php endif; ?>
