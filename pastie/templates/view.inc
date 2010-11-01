<?php
require_once PASTIE_TEMPLATES . '/recent.inc';
?>
<div id="showpaste">
    <div class="pasteHeader">
        <h2 class="pasteTitle"><?php echo $paste['title'] ? $paste['title'] : _("[Untitled]"); ?></h2>
        By <span class="pasteOwner"><?php echo $paste['owner']; ?></span>
        on <span class="pasteDate"><?php echo $paste['timestamp']; ?></span>
    </div>
    <div class="pasteContents">
    <?php echo $output; ?>
    </div>
</div>
<br style="clear:both;">
