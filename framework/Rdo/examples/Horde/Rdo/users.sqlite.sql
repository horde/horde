-- $Horde: framework/Rdo/examples/Horde/Rdo/users.sqlite.sql,v 1.1 2008/03/05 20:37:32 chuck Exp $
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255),
    favorite_id INTEGER,
    phone VARCHAR(20),
    created VARCHAR(10),
    updated VARCHAR(10)
);
