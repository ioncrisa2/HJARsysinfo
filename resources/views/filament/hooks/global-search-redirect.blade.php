<script>
(() => {
    if (window.__globalSearchRedirectBound) {
        return;
    }

    window.__globalSearchRedirectBound = true;

    const searchUrl = @js($searchUrl);

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
            return;
        }

        const target = event.target;

        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.type !== 'search' || !target.closest('.fi-global-search-field')) {
            return;
        }

        const query = target.value.trim();

        if (query === '') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const url = new URL(searchUrl, window.location.origin);
        url.searchParams.set('q', query);

        window.location.assign(url.toString());
    }, true);
})();
</script>
