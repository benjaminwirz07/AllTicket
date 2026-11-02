(() => {
    const browser = document.querySelector('[data-news-browser]');
    if (!browser) return;

    const apiUrl = browser.dataset.listUrl;
    const featuredRoot = browser.querySelector('[data-news-featured]');
    const grid = browser.querySelector('[data-news-grid]');
    const status = browser.querySelector('[data-news-status]');
    const loadMore = browser.querySelector('[data-news-load-more]');
    const searchForm = browser.querySelector('[data-news-search]');
    const searchInput = searchForm?.querySelector('input[name="q"]');
    const categoryButtons = Array.from(browser.querySelectorAll('[data-news-category]'));
    if (!apiUrl || !featuredRoot || !grid || !status || !loadMore || !searchForm || !searchInput) return;

    let activeCategory = '';
    let searchQuery = '';
    let currentPage = 0;
    let hasMore = false;
    let requestId = 0;
    let debounceTimer;

    const create = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    const formatDate = (date) => {
        const parsed = new Date(String(date).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return '';
        return new Intl.DateTimeFormat('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(parsed);
    };

    const makeShareButton = (kind, label, icon) => {
        const button = create('button', `news-share-button news-share-button--${kind}`);
        button.type = 'button';
        button.dataset.newsShare = kind;
        button.setAttribute('aria-label', label);
        button.title = label;
        const glyph = create('i', icon);
        glyph.setAttribute('aria-hidden', 'true');
        button.append(glyph);
        return button;
    };

    const makeShareTools = (post) => {
        const tools = create('div', 'news-share-tools');
        tools.append(
            makeShareButton('whatsapp', 'Compartir por WhatsApp', 'bi bi-whatsapp'),
            makeShareButton('copy', 'Copiar enlace', 'bi bi-link-45deg')
        );
        tools.dataset.title = post.title;
        tools.dataset.url = new URL(post.url, window.location.href).href;
        return tools;
    };

    const createDate = (date) => {
        const paragraph = create('p', 'news-meta');
        const icon = create('i', 'bi bi-calendar3');
        icon.setAttribute('aria-hidden', 'true');
        paragraph.append(icon, document.createTextNode(` ${formatDate(date)}`));
        return paragraph;
    };

    const createCover = (post, className) => {
        const cover = create('div', className);
        cover.dataset.category = post.category;
        if (post.image) {
            const image = create('img', '');
            image.src = post.image.startsWith('/') ? post.image : `/${post.image}`;
            image.alt = `Portada de la noticia: ${post.title}`;
            image.loading = 'lazy';
            cover.append(image);
        } else {
            const label = create('span', 'news-cover__fallback', post.category);
            label.setAttribute('aria-hidden', 'true');
            cover.append(label);
        }
        return cover;
    };

    const renderFeatured = (post) => {
        featuredRoot.replaceChildren();
        if (!post) {
            featuredRoot.hidden = true;
            return;
        }
        featuredRoot.hidden = false;

        const article = create('article', 'news-feature-card');
        article.append(createCover(post, 'news-feature-card__cover'));

        const content = create('div', 'news-feature-card__content');
        content.append(create('p', 'news-category', post.category.toUpperCase()));
        const title = create('h2', '', post.title);
        content.append(title);
        content.append(create('p', 'news-feature-card__summary', post.summary));
        const meta = create('p', 'news-meta');
        meta.textContent = `${post.author} · ${post.reading_minutes} min de lectura`;
        content.append(meta);

        const actions = create('div', 'news-feature-card__actions');
        const link = create('a', 'news-primary-button', 'Leer nota');
        link.href = post.url;
        link.setAttribute('aria-label', `Leer nota: ${post.title}`);
        actions.append(link, makeShareTools(post));
        content.append(actions);
        article.append(content);
        featuredRoot.append(article);
    };

    const createStoryCard = (post) => {
        const article = create('article', 'news-story-card');
        const link = create('a', 'news-story-card__link');
        link.href = post.url;
        link.setAttribute('aria-label', `Leer nota: ${post.title}`);
        link.append(createCover(post, 'news-story-card__cover'));

        const content = create('div', 'news-story-card__content');
        content.append(create('p', 'news-category', post.category));
        content.append(create('h3', '', post.title));
        content.append(create('p', 'news-story-card__summary', post.summary));
        content.append(createDate(post.created_at));
        const author = create('p', 'news-meta');
        author.textContent = `${post.author} · ${post.reading_minutes} min de lectura`;
        content.append(author);
        link.append(content);

        article.append(link, makeShareTools(post));
        return article;
    };

    const makeSkeleton = () => {
        const card = create('div', 'news-card-skeleton news-skeleton');
        card.setAttribute('aria-hidden', 'true');
        card.append(create('span'), create('i'), create('b'), create('small'));
        return card;
    };

    const setActiveCategory = (category) => {
        activeCategory = category;
        categoryButtons.forEach((button) => {
            const active = button.dataset.newsCategory === category;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    };

    const setLoading = (append) => {
        grid.setAttribute('aria-busy', 'true');
        status.textContent = '';
        loadMore.disabled = true;
        if (append) {
            for (let i = 0; i < 3; i += 1) grid.append(makeSkeleton());
        } else {
            renderFeatured(null);
            grid.replaceChildren(makeSkeleton(), makeSkeleton(), makeSkeleton());
            loadMore.hidden = true;
        }
    };

    const clearSkeletons = () => grid.querySelectorAll('.news-card-skeleton').forEach((item) => item.remove());

    const fetchPage = async (page, append = false) => {
        const currentRequest = ++requestId;
        setLoading(append);
        const url = new URL(apiUrl, window.location.href);
        url.searchParams.set('page', String(page));
        url.searchParams.set('category', activeCategory);
        url.searchParams.set('q', searchQuery);

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (!response.ok || !result || !Array.isArray(result.articles)) {
                throw new Error(result?.error || 'No se pudieron cargar las noticias.');
            }
            if (currentRequest !== requestId) return;

            clearSkeletons();
            if (!append) {
                renderFeatured(result.featured);
                grid.replaceChildren();
            }
            result.articles.forEach((post) => grid.append(createStoryCard(post)));
            currentPage = page;
            hasMore = Boolean(result.hasMore);
            loadMore.hidden = !hasMore;
            loadMore.disabled = false;
            grid.setAttribute('aria-busy', 'false');

            if (!result.featured && result.articles.length === 0) {
                status.textContent = 'No encontramos noticias con esos filtros.';
                const clear = create('button', 'news-clear-filters', 'Limpiar filtros');
                clear.type = 'button';
                clear.addEventListener('click', () => {
                    searchInput.value = '';
                    searchQuery = '';
                    setActiveCategory('');
                    fetchPage(1);
                });
                status.append(' ', clear);
            } else {
                status.textContent = '';
            }
        } catch (error) {
            if (currentRequest !== requestId) return;
            clearSkeletons();
            loadMore.disabled = false;
            grid.setAttribute('aria-busy', 'false');
            status.textContent = error instanceof Error ? error.message : 'No se pudieron cargar las noticias.';
            if (!append && grid.childElementCount === 0) {
                const retry = create('button', 'news-clear-filters', 'Intentar de nuevo');
                retry.type = 'button';
                retry.addEventListener('click', () => fetchPage(1));
                status.append(' ', retry);
            }
        }
    };

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setActiveCategory(button.dataset.newsCategory ?? '');
            fetchPage(1);
        });
    });

    searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        searchQuery = searchInput.value.trim();
        fetchPage(1);
    });

    searchInput.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            searchQuery = searchInput.value.trim();
            fetchPage(1);
        }, 250);
    });

    loadMore.addEventListener('click', () => {
        if (hasMore) fetchPage(currentPage + 1, true);
    });

    browser.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-news-share]');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        const tools = button.closest('.news-share-tools');
        const title = tools?.dataset.title ?? '';
        const url = tools?.dataset.url ?? window.location.href;
        const message = `${title} - ${url}`;

        if (button.dataset.newsShare === 'whatsapp') {
            window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank', 'noopener,noreferrer');
            return;
        }

        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                const temporary = create('textarea', '');
                temporary.value = url;
                temporary.setAttribute('readonly', '');
                temporary.style.position = 'fixed';
                temporary.style.opacity = '0';
                document.body.append(temporary);
                temporary.select();
                const copied = document.execCommand('copy');
                temporary.remove();
                if (!copied) throw new Error('No se pudo copiar el enlace.');
            }
            status.textContent = 'Enlace copiado.';
        } catch {
            status.textContent = 'No se pudo copiar el enlace. Podés copiar la dirección desde el navegador.';
        }
    });

    fetchPage(1);
})();
