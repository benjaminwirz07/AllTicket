(() => {
    const main = document.querySelector("#main-content main");
    if (main) {
        main.classList.add("page-enter");
    }

    const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (prefersReducedMotion || !("IntersectionObserver" in window)) {
        return;
    }

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
