CREATE TABLE IF NOT EXISTS photographe (
    id           VARCHAR(36)  NOT NULL,
    nom          VARCHAR(255) NOT NULL,
    pseudo       VARCHAR(100) NOT NULL,
    email        VARCHAR(255) NOT NULL,
    telephone    VARCHAR(50),
    description  VARCHAR(255),
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_photographe PRIMARY KEY (id),
    CONSTRAINT uk_photographe_pseudo UNIQUE (pseudo)
);