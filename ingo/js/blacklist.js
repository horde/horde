var IngoBlacklist = {
    onDomLoad: function()
    {
        $('actionvalue').observe('change', function(e) {
            if ($F(e.element())) {
                $('action_folder').setValue(1);
            }
        });

        $('blacklist_return').observe('click', function(e) {
            document.location.href = IngoBlacklist.filtersurl;
            e.stop();
        }.bind(this));
    }
};

document.observe('dom:loaded', IngoBlacklist.onDomLoad.bind(IngoBlacklist));
