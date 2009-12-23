CREATE TABLE horde_signups (
    user_name VARCHAR(255) NOT NULL,
    signup_date INTEGER NOT NULL,
    signup_host VARCHAR(255) NOT NULL,
    signup_data TEXT NOT NULL,
    PRIMARY KEY (user_name)
);
