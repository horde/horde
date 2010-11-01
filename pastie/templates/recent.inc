<?php
$uuid = Horde::url('uuid');
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
    $ago = Horde_Date_Utils::relativeDateTime($r['timestamp']->timestamp());
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
