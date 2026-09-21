const Auth = {
    getUsuario() {
        const raw = sessionStorage.getItem('usuario');
        return raw ? JSON.parse(raw) : null;
    },
    setUsuario(u) {
        sessionStorage.setItem('usuario', JSON.stringify(u));
    },
    isAdmin() {
        const r = this.getUsuario()?.rol_id;
        return r === 2 || r === 3;
    },
    isSuperAdmin() {
        return this.getUsuario()?.rol_id === 3;
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
