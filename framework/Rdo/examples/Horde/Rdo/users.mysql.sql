-- $Horde: framework/Rdo/examples/Horde/Rdo/users.mysql.sql,v 1.1 2008/03/05 20:37:32 chuck Exp $
CREATE TABLE users (
    id INT(11) auto_increment  NOT NULL,
    name varchar(255),
    favorite_id int(11),
    phone varchar(20),
    created varchar(10),
    updated varchar(10),

    PRIMARY KEY  (id)
);
