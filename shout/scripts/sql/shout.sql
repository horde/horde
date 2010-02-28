CREATE TABLE shout_contexts (
    context_name VARCHAR(15) NOT NULL
);

CREATE TABLE shout_menus (
    context_name VARCHAR(15) NOT NULL,
    menu_name VARCHAR(15) NOT NULL,
    menu_description VARCHAR(255),
    menu_soundfile VARCHAR(80) NOT NULL
);
