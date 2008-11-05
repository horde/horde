function saveSearch(url) {
    RedBox.loading();
    new Ajax.Request(url, {
                parameters: {
                    ajax: 1
                },
                method: 'get',
                onSuccess: function(transport) {
                    RedBox.showHtml('<div id="RB_info">' + transport.responseText + '</div>');
                },
                onFailure: function(transport) {
                    RedBox.close();
                }
            });
}