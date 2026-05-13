const IS_LOCAL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const BASE_URL = IS_LOCAL ? '/sistema_tickets/public' : '';

const Auth = {
    getUsuario() {
        const raw = sessionStorage.getItem('usuario');
        return raw ? JSON.parse(raw) : null;
    },
    setUsuario(u) {
        sessionStorage.setItem('usuario', JSON.stringify(u));
    },
    isAdmin() {
        return this.getUsuario()?.rol_id === 2;
    },
    requireAuth() {
        if (!this.getUsuario()) {
            window.location.href = BASE_URL + '/pages/login.html';
        }
    },
    requireAdmin() {
        this.requireAuth();
        if (!this.isAdmin()) {
            window.location.href = BASE_URL + '/index.php';
        }
    },
    async logout() {
        await api.auth.logout();
        sessionStorage.clear();
        window.location.href = BASE_URL + '/pages/login.html';
    },
};
