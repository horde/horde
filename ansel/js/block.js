document.observe('dom:loaded', function() {
    Ansel.previewImage = function(e, image_id) {
        $('ansel_preview').style.left = Event.pointerX(e) + 'px';
        $('ansel_preview').style.top = Event.pointerY(e) + 'px';
        new Ajax.Updater({ success:'ansel_preview' },
                         Ansel.conf['BASE_URI'] + '/preview.php',
                         {
                            method: 'get',
                            parameters: 'image=' + image_id,
                            onsuccess: $('ansel_preview').show()});
    };

    var preview = new Element('div', { 'id': 'ansel_preview' });
    $(document.body).insert(preview);
});