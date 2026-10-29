/**
 * Learning page: mark a lesson as done and move between lessons without reloading the page.
 *
 * Only the [data-learn] block is swapped; Alpine picks up the new markup by itself.
 * Every link and form keeps working as a normal request when a fetch fails.
 */
const ROOT = '[data-learn]';

let currentUrl = withoutHash(window.location.href);
let pendingVisit = null;
let toastTimer = null;

export function initLearning() {
    history.replaceState({ learn: true }, '');

    document.addEventListener('click', onLinkClick);
    document.addEventListener('submit', onCompleteSubmit);
    window.addEventListener('popstate', (event) => {
        // Hash-only jumps (e.g. #hoi-dap) stay on the same lesson.
        if (event.state?.learn && withoutHash(window.location.href) !== currentUrl) {
            visit(window.location.href, { push: false });
        }
    });
}

function onLinkClick(event) {
    const link = event.target.closest('a[data-learn-link]');

    if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    event.preventDefault();
    visit(link.href);
}

async function visit(url, { push = true } = {}) {
    pendingVisit?.abort();
    pendingVisit = new AbortController();

    const root = document.querySelector(ROOT);
    root.setAttribute('aria-busy', 'true');
    root.classList.add('opacity-60');

    try {
        const response = await fetch(url, {
            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            signal: pendingVisit.signal,
        });
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = page.querySelector(ROOT);

        // Redirected away (e.g. lesson locked) or an error page: let the browser handle it.
        if (! response.ok || ! next) {
            throw new Error(`Cannot load ${url}`);
        }

        const outlineScroll = root.querySelector('[data-learn-outline]')?.scrollTop ?? 0;
        root.replaceWith(next);

        const outline = next.querySelector('[data-learn-outline]');
        if (outline) {
            outline.scrollTop = outlineScroll;
            outline.querySelector('[aria-current="page"]')?.scrollIntoView({ block: 'nearest' });
        }

        document.title = page.title;
        currentUrl = withoutHash(response.url);

        if (push) {
            history.pushState({ learn: true }, '', response.url);
        }

        window.scrollTo({ top: 0 });
        next.querySelector('h1')?.focus({ preventScroll: true });
    } catch (error) {
        if (error.name !== 'AbortError') {
            window.location.href = url;
        }
    }
}

async function onCompleteSubmit(event) {
    const form = event.target.closest('form[data-learn-complete]');

    if (! form) {
        return;
    }

    event.preventDefault();
    const buttons = form.querySelectorAll('button');
    buttons.forEach((button) => (button.disabled = true));

    let result;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (! response.ok) {
            throw new Error(`Status ${response.status}`);
        }

        result = await response.json();
    } catch {
        // Nothing was saved (network error, expired session...): fall back to a normal post.
        form.submit();

        return;
    }

    if (result.certificate_url) {
        window.location.href = result.certificate_url;

        return;
    }

    updateProgress(form.dataset.learnComplete, result);
    buttons.forEach((button) => (button.disabled = false));
    showToast(result.message);

    if (result.next_url) {
        visit(result.next_url);
    }
}

function updateProgress(lessonId, { completed, progress }) {
    const root = document.querySelector(ROOT);

    root.querySelector('[data-learn-progress-bar]').style.width = `${progress}%`;
    root.querySelector('[data-learn-progress-text]').textContent = progress;
    root.querySelector('[data-learn-done]').classList.toggle('hidden', ! completed);
    root.querySelector('[data-learn-todo]').classList.toggle('hidden', completed);

    const check = root.querySelector(`[data-learn-check="${lessonId}"]`);
    if (check) {
        ['border-emerald-500', 'bg-emerald-500', 'text-white'].forEach((name) => check.classList.toggle(name, completed));
        ['border-gray-300', 'text-transparent'].forEach((name) => check.classList.toggle(name, ! completed));
    }
}

function showToast(message) {
    const toast = document.querySelector('[data-learn-toast]');

    if (! toast || ! message) {
        return;
    }

    toast.querySelector('[data-learn-toast-text]').textContent = message;
    toast.classList.replace('hidden', 'flex');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.replace('flex', 'hidden'), 3000);
}

function withoutHash(url) {
    return url.split('#')[0];
}
