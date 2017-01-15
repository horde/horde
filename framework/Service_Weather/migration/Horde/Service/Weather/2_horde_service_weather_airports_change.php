<?php
/**
 * Migration to move from retired NOAA dataset to the station list provided by
 * https://github.com/datasets/airport-codes.
 *
 * NOTE: This only generates the schema. To populate data, one must run
 * the horde-service-weather-metar-database script.
 */
class HordeServiceWeatherAirportsChange extends Horde_Db_Migration_Base
{
    protected $_fileContents;

    public function up()
    {
        // Get rid of the old data.
        $this->down();
        $t = $this->createTable('horde_metar_airports', array('autoincrementKey' => array('id')));
        $t->column('id', 'integer');
        $t->column('icao', 'string', array('limit' => 4));
        $t->column('name', 'string', array('limit' => 80));
        $t->column('state', 'string', array('limit' => 4));
        $t->column('country', 'string', array('limit' => 50));
        $t->column('municipality', 'string', array('limit' => 80));
        $t->column('latitude', 'float', array('default' => 0));
        $t->column('longitude', 'float', array('default' => 0));
        $t->column('elevation', 'float', array('default' => 0));
        $t->end();
    }

    public function down()
    {
        $tableList = $this->tables();
        if (in_array('horde_metar_airports', $tableList)) {
            $this->dropTable('horde_metar_airports');
        }
    }

}
