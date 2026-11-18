// Busca cada barra de filtros que tenga botones de desplazamiento horizontal.
document.querySelectorAll('.filter-chips-wrapper').forEach(function (wrapper) {
  // Guarda la lista desplazable y sus botones dentro del contenedor actual.
  var chips = wrapper.querySelector('.filter-chips');
  var buttons = wrapper.querySelectorAll('.filter-scroll-button');

  // Si el contenedor no tiene una lista de filtros, omite su configuración.
  if (!chips) {
    return;
  }

  // Actualiza el desborde y habilita o deshabilita los botones según la posición.
  function updateButtons() {
    // Calcula cuánto contenido queda fuera de la vista horizontal.
    var maxScroll = chips.scrollWidth - chips.clientWidth;
    // Detecta si el desplazamiento está al inicio o al final.
    var atStart = chips.scrollLeft <= 1;
    var atEnd = chips.scrollLeft >= maxScroll - 1;

    // Indica en el contenedor si hay desborde y en qué extremo se encuentra.
    wrapper.classList.toggle('has-overflow', maxScroll > 1);
    wrapper.classList.toggle('at-start', atStart);
    wrapper.classList.toggle('at-end', atEnd);

    // Deshabilita el botón izquierdo al comenzar y el derecho al llegar al final.
    buttons.forEach(function (button) {
      var direction = Number(button.dataset.scrollDirection);
      button.disabled = direction < 0 ? atStart : atEnd;
    });
  }

  // Configura el desplazamiento al hacer clic en cualquiera de los botones.
  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      var direction = Number(button.dataset.scrollDirection);
      // Respeta la preferencia de movimiento reducido del usuario.
      var behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ? 'auto'
        : 'smooth';

      // Avanza al menos 160 píxeles o el 75 % del ancho visible.
      chips.scrollBy({
        left: direction * Math.max(160, chips.clientWidth * 0.75),
        behavior: behavior
      });
    });
  });

  // Recalcula los estados al desplazarse o cambiar el tamaño de la ventana.
  chips.addEventListener('scroll', updateButtons, { passive: true });
  window.addEventListener('resize', updateButtons);

  // Hace visible el filtro activo si quedó parcialmente fuera de la barra.
  var selectedChip = chips.querySelector('.filter-chip.active');
  if (selectedChip) {
    var chipBounds = selectedChip.getBoundingClientRect();
    var chipsBounds = chips.getBoundingClientRect();

    // Si el filtro activo quedó a la derecha, desplaza la barra hacia él.
    if (chipBounds.right > chipsBounds.right - 36) {
      chips.scrollLeft += chipBounds.right - (chipsBounds.right - 36);
    // Si quedó a la izquierda, hace que vuelva a mostrarse.
    } else if (chipBounds.left < chipsBounds.left + 36) {
      chips.scrollLeft -= chipsBounds.left + 36 - chipBounds.left;
    }
  }

  // Ejecuta el cálculo inicial de desborde y estado de los botones.
  updateButtons();
});
