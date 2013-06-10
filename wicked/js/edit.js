var WickedEdit = {
    loadPreview: function()
    {
        var f = $('wicked-edit'), oldAction = f.action;

        f.action = 'preview.php';
        f.target = '_blank';
        f.submit();
        f.action = oldAction;
        f.target = '';
    },

    onDomLoad: function()
    {
        $('wicked-preview').observe('click', this.loadPreview);
    }
};

document.observe('dom:loaded', WickedEdit.onDomLoad.bind(WickedEdit));
