-- =========================================================
-- TABLE : notification
-- =========================================================
CREATE TABLE IF NOT EXISTS notification (
    id                VARCHAR(36)  NOT NULL,
    galerie_privee_id VARCHAR(36)  NOT NULL,
    type_evenement    VARCHAR(50)  NOT NULL,
    envoyee_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    succes            BOOLEAN      NOT NULL DEFAULT FALSE,

    CONSTRAINT pk_notification PRIMARY KEY (id)
);