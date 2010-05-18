<?php
$uuid = Horde::applicationUrl('uuid');
?>
<div id="recentPastes">
<h2>Recent Pastings</h2>
<hr>
<?php
$now = new DateTime;
foreach ($recent as $r) {
    if (empty($r['title'])) {
      $r['title'] = _("[Untitled]");
    }
    $then = new DateTime('@' . $r['timestamp']->timestamp());
    $ago = $now->diff($then, true);
    $days = $ago->format("%a");
    if ($days > 0) {
        $ago = $ago->format("%a days %H:%i ago");
    } else {
        $ago = $ago->format("%H:%i min ago");
    }
?>
    <div class="recentPaste">
        <span class="pasteTitle"><a href="<?php echo $uuid . '/' . $r['uuid']; ?>"><?php echo $r['title']; ?></a></span><br>
        <span class="pasteAuthor"><?php echo $r['owner']; ?></span> |
        <span class="pasteSyntax"><?php echo $r['syntax']; ?></span> |
        <span class="pasteDate"><?php echo $ago; ?></span>
    </div>
    <hr>
<?php } ?>
</div>
