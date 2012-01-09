--
-- Copyright 2012 Horde LLC (http://www.horde.org/)
--
-- @author     Ralf Lang <lang@b1-systems.de>
-- @category   Horde
-- @package    Rdo
-- @subpackage UnitTests
--
INSERT INTO test_somelazybaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('1', '1', 'First Base Thing');
INSERT INTO test_somelazybaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('2', '2', 'Second Base Thing');
INSERT INTO test_somelazybaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('3', '99999', 'Third Base Thing with invalid relation');
INSERT INTO test_somelazybaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('4', '', 'Fourth Base Thing with empty string relation');
INSERT INTO test_someeagerbaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('1', '1', 'First Base Thing');
INSERT INTO test_someeagerbaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('2', '2', 'Second Base Thing');
INSERT INTO test_someeagerbaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('3', '99999', 'Third Base Thing with invalid relation');
INSERT INTO test_someeagerbaseobjects (baseobject_id, relatedthing_id, atextproperty) VALUES ('4', '', 'Fourth Base Thing with empty string relation');
INSERT INTO test_relatedthings (relatedthing_id, relatedthing_textproperty, relatedthing_intproperty) VALUES ('1', 'First Related Thing', '100');
INSERT INTO test_relatedthings (relatedthing_id, relatedthing_textproperty, relatedthing_intproperty) VALUES ('2', 'Second Related Thing', '200');
