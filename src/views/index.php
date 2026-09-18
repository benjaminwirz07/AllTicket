<?php
include('./_layouts/layout.php');
?>

<!-- Estilos específicos para el index y sus formularios -->
<link rel="stylesheet" href="/assets/css/index.css">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Header / Navigation -->
<header>
  <nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-allticket border-bottom border-dark-subtle shadow-sm">
    <div class="container-fluid container-xl">
      <!-- Logo Principal de AllTicket -->
      <a class="navbar-brand d-flex align-items-center py-1" href="/src/views/index.php">
        <img src="/assets/img/logo-Allticket1.png" alt="AllTicket Logo" class="brand-logo">
      </a>
      
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAllTicket" aria-controls="navbarAllTicket" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarAllTicket">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
          <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="/src/views/index.php">Inicio</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#eventos">Eventos & Recitales</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#como-funciona">Cómo funciona</a>
          </li>
        </ul>
        
        <div class="d-flex align-items-center gap-3">
          <?php if (isset($_SESSION['user'])): ?>
            <span class="text-light-50 small d-none d-md-inline">
              <i class="bi bi-person-circle me-1"></i> Hola, <?= htmlspecialchars($_SESSION['user']['nombre'] ?? 'Usuario') ?>
            </span>
            <a href="/src/controllers/auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">
              <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
            </a>
          <?php else: ?>
            <a href="/src/views/auth/login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Iniciar Sesión</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
</header>

<main class="bg-main-dark text-light min-vh-100">

  <!-- Hero Section con Fondo Animado Neón -->
  <section class="hero-section hero-animated-bg text-center text-lg-start position-relative py-5">
    <div class="container-xl py-4">
      <div class="row align-items-center gy-4">
        <div class="col-lg-7">
          <span class="badge bg-violet-neon text-uppercase px-3 py-2 mb-3 rounded-pill fw-semibold">
            <i class="bi bi-music-note-beamed me-1"></i> Entradas Oficiales
          </span>
          <h1 class="display-4 fw-extrabold text-white mb-3">
            Viví la música en vivo.<br>
            <span class="text-gradient">Encontrá tu próximo recital.</span>
          </h1>
          <p class="lead text-secondary-custom mb-4">
            Elegí tus eventos favoritos, asegurá tus entradas en segundos y disfrutá del show sin preocupaciones.
          </p>
          <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
            <a href="#eventos" class="btn btn-violet btn-lg px-4 rounded-3">
              <i class="bi bi-search me-2"></i>Explorar eventos
            </a>
            <a href="#como-funciona" class="btn btn-outline-custom btn-lg px-4 rounded-3">
              Conocer más
            </a>
          </div>
        </div>
        <div class="col-lg-5 text-center">
          <div class="hero-card-img p-2 rounded-4 border border-secondary-subtle">
            <img src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?auto=format&fit=crop&w=800&q=80" alt="Concierto en vivo AllTicket" class="img-fluid rounded-4">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Buscador -->
  <section class="py-4 border-y-dark">
    <div class="container-xl">
      <form class="search-form-card p-4 rounded-4 border border-secondary-subtle" action="#eventos" method="GET">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label text-light-50 small fw-semibold">Buscar Artista o Evento</label>
            <div class="input-group">
              <span class="input-group-text custom-input-text"><i class="bi bi-search"></i></span>
              <input type="text" name="q" class="form-control custom-input" placeholder="Ej. Duki, Divididos, Festival...">
            </div>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label text-light-50 small fw-semibold">Género / Categoría</label>
            <select name="categoria" class="form-select custom-input">
              <option value="">Todas las categorías</option>
              <option value="rock">Rock</option>
              <option value="reggaeton">Reggaetón / Trap</option>
              <option value="pop">Pop</option>
              <option value="electronica">Electrónica</option>
            </select>
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label text-light-50 small fw-semibold">Ubicación</label>
            <select name="ciudad" class="form-select custom-input">
              <option value="">Todas las ciudades</option>
              <option value="buenos-aires">Buenos Aires</option>
              <option value="cordoba">Córdoba</option>
              <option value="rosario">Rosario</option>
            </select>
          </div>
          <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-violet w-100 py-2 rounded-3">
              <i class="bi bi-funnel-fill me-1"></i> Filtrar
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>

  <!-- Eventos Destacados -->
  <section id="eventos" class="py-5">
    <div class="container-xl">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="h3 fw-bold text-white mb-1">Próximos Recitales</h2>
          <p class="text-secondary-custom mb-0">Comprá tus entradas oficiales para los mejores shows</p>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-12 col-sm-6 col-lg-3">
          <article class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=600&q=80" class="card-img-top event-img" alt="Recital de Rock">
              <span class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill">Rock</span>
            </div>
            <div class="card-body d-flex flex-column p-4">
              <h3 class="h5 card-title fw-bold text-white mb-2">Festival de Rock 2026</h3>
              <p class="text-secondary-custom small mb-2"><i class="bi bi-geo-alt me-1 text-primary-neon"></i> Estadio River Plate, CABA</p>
              <p class="text-secondary-custom small mb-3"><i class="bi bi-calendar-event me-1 text-primary-neon"></i> 15 de Noviembre, 21:00 hs</p>
              <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                <span class="fs-5 fw-bold text-white">$ 25.000</span>
                <a href="#" class="btn btn-sm btn-outline-violet rounded-pill px-3">Comprar</a>
              </div>
            </div>
          </article>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <article class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=600&q=80" class="card-img-top event-img" alt="Noche Urbana Trap">
              <span class="badge bg-blue-neon position-absolute top-0 end-0 m-3 rounded-pill">Urban / Trap</span>
            </div>
            <div class="card-body d-flex flex-column p-4">
              <h3 class="h5 card-title fw-bold text-white mb-2">Noche Urbana Live</h3>
              <p class="text-secondary-custom small mb-2"><i class="bi bi-geo-alt me-1 text-primary-neon"></i> Movistar Arena, CABA</p>
              <p class="text-secondary-custom small mb-3"><i class="bi bi-calendar-event me-1 text-primary-neon"></i> 20 de Diciembre, 20:00 hs</p>
              <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                <span class="fs-5 fw-bold text-white">$ 30.000</span>
                <a href="#" class="btn btn-sm btn-outline-violet rounded-pill px-3">Comprar</a>
              </div>
            </div>
          </article>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <article class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=600&q=80" class="card-img-top event-img" alt="Electro Night">
              <span class="badge bg-violet-neon position-absolute top-0 end-0 m-3 rounded-pill">Electrónica</span>
            </div>
            <div class="card-body d-flex flex-column p-4">
              <h3 class="h5 card-title fw-bold text-white mb-2">Electro Sunset Session</h3>
              <p class="text-secondary-custom small mb-2"><i class="bi bi-geo-alt me-1 text-primary-neon"></i> Mandarine Park, CABA</p>
              <p class="text-secondary-custom small mb-3"><i class="bi bi-calendar-event me-1 text-primary-neon"></i> 05 de Enero, 18:00 hs</p>
              <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                <span class="fs-5 fw-bold text-white">$ 20.000</span>
                <a href="#" class="btn btn-sm btn-outline-violet rounded-pill px-3">Comprar</a>
              </div>
            </div>
          </article>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
          <article class="card event-card h-100 bg-card-dark text-light border-0 rounded-4 overflow-hidden shadow">
            <div class="position-relative">
              <img src="https://images.unsplash.com/photo-1459749411175-04bf5292ceea?auto=format&fit=crop&w=600&q=80" class="card-img-top event-img" alt="Pop Tour">
              <span class="badge bg-blue-neon position-absolute top-0 end-0 m-3 rounded-pill">Pop</span>
            </div>
            <div class="card-body d-flex flex-column p-4">
              <h3 class="h5 card-title fw-bold text-white mb-2">Pop World Tour</h3>
              <p class="text-secondary-custom small mb-2"><i class="bi bi-geo-alt me-1 text-primary-neon"></i> Estadio Vélez Sarsfield, CABA</p>
              <p class="text-secondary-custom small mb-3"><i class="bi bi-calendar-event me-1 text-primary-neon"></i> 12 de Febrero, 21:30 hs</p>
              <div class="mt-auto d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                <span class="fs-5 fw-bold text-white">$ 28.000</span>
                <a href="#" class="btn btn-sm btn-outline-violet rounded-pill px-3">Comprar</a>
              </div>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

</main>

<!-- Footer con Logo -->
<footer class="bg-dark-footer text-light-50 py-5 border-top border-dark-subtle">
  <div class="container-xl">
    <div class="row g-4 mb-4">
      <div class="col-12 col-md-4">
        <a class="d-inline-block text-decoration-none mb-3" href="/src/views/index.php">
          <img src="/assets/img/logo-Allticket1.png" alt="AllTicket Logo" class="brand-logo">
        </a>
        <p class="small text-secondary-custom">
          Plataforma líder en venta de entradas digitales para conciertos, recitales y eventos musicales en vivo.
        </p>
      </div>
      <div class="col-6 col-md-2 offset-md-2">
        <h5 class="text-white small fw-bold text-uppercase mb-3">Secciones</h5>
        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
          <li><a href="/src/views/index.php" class="text-secondary-custom text-decoration-none">Inicio</a></li>
          <li><a href="#eventos" class="text-secondary-custom text-decoration-none">Eventos</a></li>
          <li><a href="#como-funciona" class="text-secondary-custom text-decoration-none">Cómo funciona</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-4">
        <h5 class="text-white small fw-bold text-uppercase mb-3">Soporte & Contacto</h5>
        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0 text-secondary-custom">
          <li><i class="bi bi-envelope me-2"></i> soporte@allticket.com</li>
          <li><i class="bi bi-shield-check me-2"></i> Compra 100% segura</li>
        </ul>
      </div>
    </div>
    <div class="border-top border-secondary-subtle pt-4 text-center small text-secondary-custom">
      <p class="mb-0">&copy; <?= date('Y') ?> AllTicket. Todos los derechos reservados.</p>
    </div>
  </div>
</footer>