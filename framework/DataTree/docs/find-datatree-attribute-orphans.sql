SELECT DISTINCT da.datatree_id FROM horde_datatree_attributes da LEFT JOIN horde_datatree d ON da.datatree_id = d.datatree_id WHERE d.datatree_id IS NULL
