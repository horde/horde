var IngoWhitelist = {
    onDomLoad: function()
    {
        $('whitelist_return').observe('click', function(e) {
            document.location.href = this.filtersurl;
            e.stop();
        }.bind(this));
    }
};

document.observe('dom:loaded', IngoWhitelist.onDomLoad.bind(IngoWhitelist));
