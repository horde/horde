<?php
$uuid = Horde::applicationUrl('uuid');
?>
<div id="recentPastes">
<h2>Recent Pastings</h2>
<hr>
<?php foreach ($recent as $r) { ?>
    <div class="recentPaste">
        <span class="pasteTitle"><a href="<?php echo $uuid . '/' . $r['uuid']; ?>"><?php echo $r['title']; ?></a></span><br>
        <span class="pasteAuthor"><?php echo $r['owner']; ?></span> |
        <span class="pasteSyntax"><?php echo $r['syntax']; ?></span> |
        <span class="pasteDate"><?php echo $r['timestamp']; ?></span>
    </div>
    <hr>
<?php } ?>
</div>