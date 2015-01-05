/**
 * @copyright  2014-2015 Horde LLC
 * @license    LGPL-2.1 (http://www.horde.org/licenses/lgpl21)
 */

var HordeBlocks = {

    addUpdateableBlock: function(app, blockid, uid, refresh, opts)
    {
        uid = $(uid);

        new PeriodicalExecuter(function() {
            HordeCore.doAction('blockAutoUpdate', {
                app: app,
                blockid: blockid,
                options: opts
            }, {
                callback: uid.update.bind(uid)
            });
        }, refresh / 1000);
    }

};
