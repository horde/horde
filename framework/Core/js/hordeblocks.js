var HordeBlocks = {

    addUpdateableBlock: function(app, blockid, uid, refresh, opts)
    {
        var block = {
            'app': app,
            'blockid': blockid,
            'refresh': refresh,
            'uid': uid,
            'options': opts
        };
        setTimeout(this.update.curry(block).bind(this), block.refresh);
    },

    update: function(block)
    {
        HordeCore.doAction('blockAutoUpdate',
            { app: block.app, blockid: block.blockid, options: block.options },
            {
                callback: function(r) {
                    $(block.uid).update(r);
                    setTimeout(this.update.curry(block).bind(this), block.refresh);
                }.bind(this)
            }
        );
    }
};
