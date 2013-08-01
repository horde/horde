<div class="header">
 <span class="smallheader rightFloat"><?php echo strftime('%B %d, %Y', $date) ?></span>
<?php if ($homepage): ?>
 <a href="<?php echo $homepage ?>" target="_blank" class="header">
  <?php printf(_("%s by %s"), $name, $author) ?>
 </a>
<?php else: ?>
 <?php printf(_("%s by %s"), $name, $author) ?>
<?php endif; ?>
</div>
<p class="solidbox" style="padding:5px">
 <?php
echo _("This image is not currently available.");
if ($registry->isAdmin('klutz:admin')) {
    $fetchurl = Horde::url('backend.php')->add(array(
        'mode' => 'fetch',
        'date' => $date,
        'index' => $index,
        'redirect' => 'comics.php',
        'action' => $actionID));
    echo '<br /><a href="' . $fetchurl . '">' .
        _("Try to fetch this comic") . '</a>';
}
 ?>
</p>
