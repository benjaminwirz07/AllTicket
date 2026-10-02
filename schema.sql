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

=====================================================
-- 2. ARTISTAS
-- =====================================================

CREATE TABLE IF NOT EXISTS artists (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =====================================================
-- 3. LUGARES
-- =====================================================

CREATE TABLE IF NOT EXISTS venues (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100),
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- =====================================================
-- 4. RECITALES
-- =====================================================

CREATE TABLE IF NOT EXISTS concerts (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    artist_id CHAR(36) NOT NULL,
    venue_id CHAR(36) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    image VARCHAR(255),
    genre VARCHAR(50),
    status ENUM('active', 'cancelled', 'finished') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (artist_id)
        REFERENCES artists(id)
        ON DELETE CASCADE,

    FOREIGN KEY (venue_id)
        REFERENCES venues(id)
        ON DELETE CASCADE
);


-- =====================================================
-- 5. TIPOS DE ENTRADAS
-- =====================================================

CREATE TABLE IF NOT EXISTS tickets (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    concert_id CHAR(36) NOT NULL,
    type VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    available INT NOT NULL,

    FOREIGN KEY (concert_id)
        REFERENCES concerts(id)
        ON DELETE CASCADE
);


-- =====================================================
-- 6. VENTAS / COMPRAS
-- =====================================================

CREATE TABLE IF NOT EXISTS sales (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id CHAR(36) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- =====================================================
-- 7. DETALLE DE VENTA
-- =====================================================

CREATE TABLE IF NOT EXISTS sale_details (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    sale_id CHAR(36) NOT NULL,
    ticket_id CHAR(36) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE CASCADE,

    FOREIGN KEY (ticket_id)
        REFERENCES tickets(id)
        ON DELETE CASCADE
);