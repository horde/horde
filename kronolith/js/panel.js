document.observe('click', function(e) {
    if (!e.isRightClick() && e.element().match('SPAN.toggleTags')) {
        e.element().up().select('.toggleTags').invoke('toggle');
    }
});
