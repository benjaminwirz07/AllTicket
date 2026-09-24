CREATE DATABASE allticket;

USE allticket;

CREATE TABLE IF NOT EXISTS users (
    id          CHAR(36)     NOT NULL DEFAULT (UUID()),
    name        VARCHAR(50)  NOT NULL,
    first_name  VARCHAR(100) NOT NULL,
    second_name VARCHAR(100) NULL,
    last_name   VARCHAR(100) NOT NULL,
    dni         VARCHAR(20)  NULL,
    phone       VARCHAR(30)  NULL,
    city        VARCHAR(100) NULL,
    province    VARCHAR(100) NULL,
    birth_date  DATE         NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_dni (dni)
    
);

CREATE TABLE IF NOT EXISTS posts (
    id         CHAR(36)     NOT NULL DEFAULT (UUID()),
    title      VARCHAR(255) NOT NULL,
    content    TEXT         NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    user_id    CHAR(36)     NOT NULL,

    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);