-- $Horde: framework/Rdo/examples/Horde/Rdo/users.pgsql.sql,v 1.1 2008/03/05 20:37:32 chuck Exp $
CREATE TABLE users (
    id bigserial NOT NULL,
    name varchar(255),
    favorite_id integer,
    phone varchar(20),
    created varchar(10),
    updated varchar(10),

    PRIMARY KEY  (id)
);
