<div id="showpaste">
    <div class="pasteHeader">
        <h2 class="pasteTitle"><?php echo $paste['title']; ?></h2>
        By <span class="pasteOwner"><?php echo $paste['owner']; ?></span>
        on <span class="pasteDate"><?php echo $paste['timestamp']; ?></span>
    </div>
    <div class="pasteContents">
    <?php echo $output; ?>
    </div>
</div>