<p><?php echo sprintf(_("Welcome, %s"), $fullname) ?></p>

<ul>
<?php foreach ($links as $key => $val): ?>
 <li><a href="<?php echo $val ?>"><?php echo $key ?></a></li>
<?php endforeach; ?>
</ul>
