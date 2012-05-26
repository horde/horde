document.observe('click', function(e) {
    if (!e.isRightClick() && e.element().up().match('SPAN.toggleTags')) {
        e.element().up().up().select('.toggleTags').invoke('toggle');
    }
});
