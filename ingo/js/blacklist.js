document.observe('dom:loaded', function() {
    $('actionvalue').observe('change', function(e) {
        if ($F(e.element())) {
            $('action_folder').setValue(1);
        }
    });
});
