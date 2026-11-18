(() => {
    // Anima la entrada inicial del contenido principal cuando la página lo permite.
    const main = document.querySelector("#main-content main");
    if (main) {
        main.classList.add("page-enter");
    }

    // Respeta la preferencia de movimiento reducido del sistema en todas las animaciones.
    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    // Rota los beneficios destacados solo mientras el módulo está visible en pantalla.
    const benefitRotator = document.querySelector("[data-benefit-rotator]");
    if (benefitRotator) {
        const benefitSlides = Array.from(benefitRotator.querySelectorAll(".trust-benefit-slide"));
        let activeBenefitIndex = benefitSlides.findIndex((slide) => slide.classList.contains("is-active"));

        if (activeBenefitIndex < 0) {
            activeBenefitIndex = 0;
        }

        const showBenefit = (index) => {
            benefitSlides.forEach((slide, slideIndex) => {
                const isActive = slideIndex === index;
                slide.classList.toggle("is-active", isActive);
                slide.setAttribute("aria-hidden", String(!isActive));
            });
            activeBenefitIndex = index;
        };

        showBenefit(activeBenefitIndex);

        if (!prefersReducedMotion && benefitSlides.length > 1) {
            let rotationTimer = null;
            const startRotation = () => {
                if (rotationTimer !== null) {
                    return;
                }

                rotationTimer = window.setInterval(() => {
                    const outgoingSlide = benefitSlides[activeBenefitIndex];
                    const nextBenefitIndex = (activeBenefitIndex + 1) % benefitSlides.length;
                    outgoingSlide.classList.remove("is-active");
                    outgoingSlide.setAttribute("aria-hidden", "true");

                    window.setTimeout(() => showBenefit(nextBenefitIndex), 600);
                }, 3300);
            };
            const stopRotation = () => {
                window.clearInterval(rotationTimer);
                rotationTimer = null;
            };

            if ("IntersectionObserver" in window) {
                const visibilityObserver = new IntersectionObserver(([entry]) => {
                    if (entry.isIntersecting) {
                        startRotation();
                    } else {
                        stopRotation();
                    }
                });
                visibilityObserver.observe(benefitRotator);
            } else {
                startRotation();
            }
        }
    }

    // Omite las revelaciones al desplazarse si no hay movimiento permitido u observador.
    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        return;
    }

    // Selecciona secciones y tarjetas que aparecerán al entrar en el área visible.
    const elements = document.querySelectorAll(
        "#main-content main > section, " +
        "#main-content main .event-card, " +
        "#main-content main .step-card, " +
        "#main-content main .admin-card, " +
        "#main-content main .admin-stat, " +
        "#main-content main > .container-xl > .card"
    );

    if (elements.length === 0) {
        return;
    }

    // Ajusta umbral, margen y demora de animación para pantallas pequeñas.
    const isMobile = window.matchMedia("(max-width: 767.98px)").matches;
    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("is-visible");
                currentObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: isMobile ? 0.01 : 0.12,
        rootMargin: isMobile ? "0px 0px 80px 0px" : "0px 0px -40px 0px"
    });

    elements.forEach((element, index) => {
        element.classList.add("scroll-reveal");
        const delay = (index % 4) * (isMobile ? 25 : 70);
        element.style.setProperty("--scroll-reveal-delay", `${delay}ms`);
        observer.observe(element);
    });
})();
