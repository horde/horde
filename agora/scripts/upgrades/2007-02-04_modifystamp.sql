ALTER TABLE agora_messages ADD message_modifystamp INT DEFAULT 0 NOT NULL;

UPDATE agora_messages SET message_modifystamp = message_timestamp;