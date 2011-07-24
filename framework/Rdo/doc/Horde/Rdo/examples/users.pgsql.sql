CREATE TABLE users (
    id bigserial NOT NULL,
    name varchar(255),
    favorite_id integer,
    phone varchar(20),
    created varchar(10),
    updated varchar(10),

    PRIMARY KEY  (id)
);
