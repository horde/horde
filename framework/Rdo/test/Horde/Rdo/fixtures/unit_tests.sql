--
-- Copyright 2012-2013 Horde LLC (http://www.horde.org/)
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

INSERT INTO test_manytomanya (a_id, a_intproperty) VALUES ('1', '200');
INSERT INTO test_manytomanya (a_id, a_intproperty) VALUES ('2', '220');
INSERT INTO test_manytomanya (a_id, a_intproperty) VALUES ('3', '230');
INSERT INTO test_manytomanya (a_id, a_intproperty) VALUES ('4', '240');
INSERT INTO test_manytomanya (a_id, a_intproperty) VALUES ('5', '250');

INSERT INTO test_manytomanyb (b_id, b_intproperty) VALUES ('11', '400');
INSERT INTO test_manytomanyb (b_id, b_intproperty) VALUES ('12', '420');
INSERT INTO test_manytomanyb (b_id, b_intproperty) VALUES ('13', '430');
INSERT INTO test_manytomanyb (b_id, b_intproperty) VALUES ('14', '440');
INSERT INTO test_manytomanyb (b_id, b_intproperty) VALUES ('15', '450');

INSERT INTO test_manythrough (a_id, b_id)  VALUES (2, 12);
INSERT INTO test_manythrough (a_id, b_id)  VALUES (2, 14);