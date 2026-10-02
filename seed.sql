USE allticket;

-- =====================================================
-- DATOS DE PRUEBA
-- =====================================================

-- =====================================================
-- 1. ARTISTAS
-- =====================================================

INSERT INTO artists (name, description, image) VALUES
('Tini',
 'Cantante argentina de pop.',
 'assets/img/tini.webp'),

('Clarent',
 'Artista argentino de música urbana.',
 'assets/img/clarent.webp'),

('Cazzu',
 'Artista argentina de música urbana.',
 'assets/img/cazzu.jpg'),

('Anuel AA',
 'Artista puertorriqueño de música urbana.',
 'assets/img/anuel.jpg'),

('Bad Gyal',
 'Artista española de música urbana.',
 'assets/img/badgyal.jpg');


-- =====================================================
-- 2. LUGARES
-- =====================================================

INSERT INTO venues (name, address, city, province, capacity) VALUES
('Movistar Arena',
 'Humboldt 450',
 'Buenos Aires',
 'Buenos Aires',
 15000),

('Estadio River Plate',
 'Av. Figueroa Alcorta 7597',
 'Buenos Aires',
 'Buenos Aires',
 70000),

('Estadio Velez Sarsfield',
 'Av. Juan B. Justo 9200',
 'Buenos Aires',
 'Buenos Aires',
 55000),

('Arena Aconcagua',
 'Av. España 1510',
 'Mendoza',
 'Mendoza',
 12000);


-- =====================================================
-- 3. RECITALES
-- =====================================================

-- TINI
INSERT INTO concerts (
    artist_id,
    venue_id,
    name,
    description,
    date,
    time,
    image,
    genre,
    status
)
SELECT
    a.id,
    v.id,
    'Tini - Tour 2026',
    'Recital de Tini.',
    '2026-11-21',
    '21:00:00',
    'assets/img/tini.webp',
    'Pop',
    'active'
FROM artists a, venues v
WHERE a.name = 'Tini'
AND v.name = 'Movistar Arena';


-- CLARENT
INSERT INTO concerts (
    artist_id,
    venue_id,
    name,
    description,
    date,
    time,
    image,
    genre,
    status
)
SELECT
    a.id,
    v.id,
    'Clarent - Live 2026',
    'Recital de Clarent.',
    '2026-12-05',
    '21:00:00',
    'assets/img/clarent.webp',
    'Urbano',
    'active'
FROM artists a, venues v
WHERE a.name = 'Clarent'
AND v.name = 'Movistar Arena';


-- BAD GYAL
INSERT INTO concerts (
    artist_id,
    venue_id,
    name,
    description,
    date,
    time,
    image,
    genre,
    status
)
SELECT
    a.id,
    v.id,
    'Bad Gyal - Live 2026',
    'Recital de Bad Gyal.',
    '2026-12-20',
    '21:00:00',
    'assets/img/badgyal.jpg',
    'Urbano',
    'active'
FROM artists a, venues v
WHERE a.name = 'Bad Gyal'
AND v.name = 'Movistar Arena';


-- CAZZU
INSERT INTO concerts (
    artist_id,
    venue_id,
    name,
    description,
    date,
    time,
    image,
    genre,
    status
)
SELECT
    a.id,
    v.id,
    'Cazzu - Live 2026',
    'Recital de Cazzu.',
    '2027-01-16',
    '21:00:00',
    'assets/img/cazzu.jpg',
    'Urbano',
    'active'
FROM artists a, venues v
WHERE a.name = 'Cazzu'
AND v.name = 'Estadio River Plate';


-- ANUEL AA
INSERT INTO concerts (
    artist_id,
    venue_id,
    name,
    description,
    date,
    time,
    image,
    genre,
    status
)
SELECT
    a.id,
    v.id,
    'Anuel AA - Live 2027',
    'Recital de Anuel AA.',
    '2027-02-20',
    '21:00:00',
    'assets/img/anuel.jpg',
    'Urbano',
    'active'
FROM artists a, venues v
WHERE a.name = 'Anuel AA'
AND v.name = 'Estadio River Plate';


-- =====================================================
-- 4. ENTRADAS
-- La card del index muestra MIN(price)
-- =====================================================

-- TINI
INSERT INTO tickets (
    concert_id,
    type,
    price,
    stock,
    available
)
SELECT
    id,
    'General',
    35000.00,
    5000,
    5000
FROM concerts
WHERE name = 'Tini - Tour 2026';


-- CLARENT
INSERT INTO tickets (
    concert_id,
    type,
    price,
    stock,
    available
)
SELECT
    id,
    'General',
    28000.00,
    3000,
    3000
FROM concerts
WHERE name = 'Clarent - Live 2026';


-- BAD GYAL
INSERT INTO tickets (
    concert_id,
    type,
    price,
    stock,
    available
)
SELECT
    id,
    'General',
    30000.00,
    3000,
    3000
FROM concerts
WHERE name = 'Bad Gyal - Live 2026';


-- CAZZU
INSERT INTO tickets (
    concert_id,
    type,
    price,
    stock,
    available
)
SELECT
    id,
    'General',
    40000.00,
    4000,
    4000
FROM concerts
WHERE name = 'Cazzu - Live 2026';


-- ANUEL AA
INSERT INTO tickets (
    concert_id,
    type,
    price,
    stock,
    available
)
SELECT
    id,
    'General',
    45000.00,
    5000,
    5000
FROM concerts
WHERE name = 'Anuel AA - Live 2027';