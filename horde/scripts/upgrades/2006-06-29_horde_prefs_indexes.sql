-- This script adds additional indexes to the horde_prefs table that should
-- improve loading of preferences from the preference table.

CREATE INDEX pref_uid_idx ON horde_prefs (pref_uid);
CREATE INDEX pref_scope_idx ON horde_prefs (pref_scope);

