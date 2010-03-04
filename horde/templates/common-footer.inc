<?php if (!$GLOBALS['browser']->isMobile()): ?>
<script type="text/javascript">
if (document.title && parent.frames && parent.frames.horde_main) {
    parent.document.title = document.title;
}
</script>
<?php $GLOBALS['notification']->notify(array('listeners' => array('audio', 'javascript'))); ?>
<?php endif; ?>
</body>
</html>
