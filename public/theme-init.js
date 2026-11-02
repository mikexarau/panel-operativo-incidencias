// Se carga antes de pintar para que el tema oscuro no parpadee al abrir la página. Es un
// archivo y no un script en línea para que la CSP pueda prohibir estos últimos.
(function () {
    var theme = null;

    try {
        theme = window.localStorage.getItem('incidents.theme');
    } catch (error) {
        // Sin almacenamiento disponible se usa la preferencia del sistema.
    }

    if (theme !== 'light' && theme !== 'dark') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    document.documentElement.dataset.theme = theme;
})();
