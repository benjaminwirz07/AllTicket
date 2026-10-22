-- =====================================================
-- ALLTICKET - ESQUEMA COMPLETO
-- Proyecto escolar
-- Motor: MySQL 8.0
-- =====================================================

-- ATENCION:
-- Este script elimina la base anterior y la crea nuevamente.
-- Ejecutar solamente cuando se quiera inicializar desde cero.
-- Si ya tenes datos cargados, usa el ALTER TABLE del rol
-- en lugar de correr este archivo.

DROP DATABASE IF EXISTS allticket;

CREATE DATABASE allticket
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE allticket;

-- =====================================================
-- 1. USUARIOS
-- =====================================================
-- role: 'user' por defecto. El rol 'admin' se asigna
-- a mano (UPDATE) y nunca desde el formulario de registro.
-- =====================================================

CREATE TABLE users (
    id          CHAR(36) NOT NULL DEFAULT (UUID()),
    usuario     VARCHAR(50) NOT NULL,
    first_name  VARCHAR(100) NOT NULL,
    second_name VARCHAR(100) NULL,
    last_name   VARCHAR(100) NOT NULL,
    dni         VARCHAR(20) NOT NULL,
    genero      ENUM('F', 'M', 'X') NOT NULL,
    phone       VARCHAR(30) NULL,
    city        VARCHAR(100) NULL,
    province    VARCHAR(100) NULL,
    birth_date  DATE NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_users_usuario (usuario),
    UNIQUE KEY uq_users_dni (dni),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- =====================================================
-- 2. PUBLICACIONES
-- =====================================================

CREATE TABLE posts (
    id         CHAR(36) NOT NULL DEFAULT (UUID()),
    title      VARCHAR(255) NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP,
    user_id    CHAR(36) NOT NULL,

    PRIMARY KEY (id),
    KEY idx_posts_user_id (user_id),

    CONSTRAINT fk_posts_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 3. ARTISTAS
-- =====================================================

CREATE TABLE artists (
    id          CHAR(36) NOT NULL DEFAULT (UUID()),
    name        VARCHAR(150) NOT NULL,
    description TEXT NULL,
    image       VARCHAR(255) NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_artists_name (name)
) ENGINE=InnoDB;

-- =====================================================
-- 4. LUGARES / VENUES
-- =====================================================

CREATE TABLE venues (
    id         CHAR(36) NOT NULL DEFAULT (UUID()),
    name       VARCHAR(150) NOT NULL,
    address    VARCHAR(255) NOT NULL,
    city       VARCHAR(100) NOT NULL,
    province   VARCHAR(100) NULL,
    capacity   INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    CONSTRAINT chk_venues_capacity
        CHECK (capacity > 0)
) ENGINE=InnoDB;

-- =====================================================
-- 5. RECITALES
-- =====================================================

CREATE TABLE concerts (
    id          CHAR(36) NOT NULL DEFAULT (UUID()),
    artist_id   CHAR(36) NOT NULL,
    venue_id    CHAR(36) NOT NULL,
    name        VARCHAR(200) NOT NULL,
    description TEXT NULL,
    date        DATE NOT NULL,
    time        TIME NOT NULL,
    image       VARCHAR(255) NULL,
    genre       VARCHAR(50) NULL,
    status      ENUM('active', 'cancelled', 'finished')
                NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_concerts_artist (artist_id),
    KEY idx_concerts_venue (venue_id),
    KEY idx_concerts_date_status (date, status),

    CONSTRAINT fk_concerts_artist
        FOREIGN KEY (artist_id)
        REFERENCES artists(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_concerts_venue
        FOREIGN KEY (venue_id)
        REFERENCES venues(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 6. TIPOS DE ENTRADAS
-- =====================================================

CREATE TABLE tickets (
    id         CHAR(36) NOT NULL DEFAULT (UUID()),
    concert_id CHAR(36) NOT NULL,
    type       VARCHAR(100) NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    stock      INT NOT NULL,
    available  INT NOT NULL,

    PRIMARY KEY (id),

    -- Necesario para comprobar que el ticket pertenece
    -- al mismo recital indicado en cada reserva.
    UNIQUE KEY uq_tickets_id_concert (id, concert_id),

    KEY idx_tickets_concert (concert_id),

    CONSTRAINT chk_tickets_price
        CHECK (price >= 0),

    CONSTRAINT chk_tickets_stock
        CHECK (stock >= 0),

    CONSTRAINT chk_tickets_available
        CHECK (available >= 0 AND available <= stock),

    CONSTRAINT fk_tickets_concert
        FOREIGN KEY (concert_id)
        REFERENCES concerts(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 7. VENTAS
-- =====================================================

CREATE TABLE sales (
    id             CHAR(36) NOT NULL DEFAULT (UUID()),
    user_id        CHAR(36) NOT NULL,
    sale_date      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total          DECIMAL(10,2) NOT NULL,
    status         ENUM('pending', 'paid', 'cancelled')
                   NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(30) NULL,

    PRIMARY KEY (id),

    KEY idx_sales_user (user_id),
    KEY idx_sales_status_date (status, sale_date),

    CONSTRAINT chk_sales_total
        CHECK (total >= 0),

    CONSTRAINT fk_sales_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 8. DETALLE DE VENTA
-- =====================================================

CREATE TABLE sale_details (
    id        CHAR(36) NOT NULL DEFAULT (UUID()),
    sale_id   CHAR(36) NOT NULL,
    ticket_id CHAR(36) NOT NULL,
    quantity  INT NOT NULL,
    price     DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (id),

    KEY idx_sale_details_sale (sale_id),
    KEY idx_sale_details_ticket (ticket_id),

    CONSTRAINT chk_sale_details_quantity
        CHECK (quantity > 0),

    CONSTRAINT chk_sale_details_price
        CHECK (price >= 0),

    CONSTRAINT fk_sale_details_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_sale_details_ticket
        FOREIGN KEY (ticket_id)
        REFERENCES tickets(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 9. RESERVAS DE ASIENTOS
-- =====================================================
--
-- held      = reserva temporal
-- confirmed = compra confirmada
-- released  = reserva cancelada/liberada
-- expired   = reserva vencida
--
-- Las reservas held deben tener expires_at.
-- La aplicacion PHP debe vencerlas cuando corresponda.
--
-- active_seat_key evita que un asiento tenga mas de una
-- reserva held/confirmed para el mismo recital.
--
-- Las reservas released/expired conservan su historial.
-- =====================================================

CREATE TABLE seat_reservations (
    id         CHAR(36) NOT NULL DEFAULT (UUID()),
    concert_id CHAR(36) NOT NULL,
    ticket_id  CHAR(36) NOT NULL,
    sale_id    CHAR(36) NOT NULL,
    seat_code  VARCHAR(20) NOT NULL,

    status ENUM('held', 'confirmed', 'released', 'expired')
           NOT NULL DEFAULT 'held',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,

    active_seat_key VARCHAR(100)
        GENERATED ALWAYS AS (
            CASE
                WHEN status IN ('held', 'confirmed')
                THEN CONCAT(concert_id, ':', seat_code)
                ELSE NULL
            END
        ) STORED,

    PRIMARY KEY (id),

    -- MySQL permite varias claves NULL en un indice UNIQUE.
    -- Por eso los asientos liberados/vencidos pueden repetirse.
    UNIQUE KEY uq_active_concert_seat (active_seat_key),

    KEY idx_reservations_concert (concert_id),
    KEY idx_reservations_ticket (ticket_id),
    KEY idx_reservations_sale (sale_id),
    KEY idx_reservations_expiration (status, expires_at),

    CONSTRAINT chk_reservation_expiration
        CHECK (
            status <> 'held'
            OR expires_at IS NOT NULL
        ),

    -- Garantiza que el ticket corresponde al mismo recital.
    CONSTRAINT fk_reservation_ticket_concert
        FOREIGN KEY (ticket_id, concert_id)
        REFERENCES tickets(id, concert_id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_reservation_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT,

    CONSTRAINT fk_reservation_concert
        FOREIGN KEY (concert_id)
        REFERENCES concerts(id)
        ON DELETE RESTRICT
        ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- =====================================================
-- 10. CHATBOT MESSAGES
-- =====================================================
CREATE TABLE IF NOT EXISTS chatbot_messages (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chat_id    CHAR(64) NOT NULL,
    sender     ENUM('user', 'assistant') NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_chatbot_messages_chat (chat_id, id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. NEWSLETTER SUBSCRIBERS
-- =====================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email      VARCHAR(254) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_subscribers_email (email)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS newsletter_rate_limits (
    ip_hash           CHAR(64) NOT NULL,
    window_started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    attempts          TINYINT UNSIGNED NOT NULL DEFAULT 0,

    PRIMARY KEY (ip_hash),
    KEY idx_newsletter_rate_window (window_started_at)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
-- =====================================================
-- FIN DEL ESQUEMA
-- =====================================================