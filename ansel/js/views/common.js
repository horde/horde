document.observe('dom:loaded', function() {
    if ($('horde-contentwrapper')) {
        $('horde-contentwrapper').style.minHeight = document.viewport.getHeight() - ($('horde-head').getHeight() + $('horde-sub').getHeight()) - 2 + 'px';
    }
});



