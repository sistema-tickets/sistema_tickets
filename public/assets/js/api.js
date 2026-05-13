const IS_LOCAL = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const BASE_URL = IS_LOCAL ? '/sistema_tickets/public' : '';
const BASE = BASE_URL + '/api';

async function request(endpoint, method = 'GET', body = null) {
    const options = {
        method,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
    };
    if (body) options.body = JSON.stringify(body);

    const res = await fetch(`${BASE}/${endpoint}`, options);
    const data = await res.json();

    if (res.status === 401) {
        sessionStorage.clear();
        window.location.href = BASE_URL + '/pages/login.html';
        return;
    }
    return data;
}

const api = {
    auth: {
        login:  (email, password) => request('auth.php?action=login', 'POST', { email, password }),
        logout: ()                 => request('auth.php?action=logout', 'POST'),
        me:     ()                 => request('auth.php?action=me'),
    },
    tickets: {
        list:        (params = {}) => request('tickets.php?' + new URLSearchParams(params)),
        get:         (id)          => request(`tickets.php?id=${id}`),
        create:      (data)        => request('tickets.php', 'POST', data),
        updateEstado:(id, data)    => request(`tickets.php?id=${id}&action=estado`, 'PUT', data),
        asignar:     (id, data)    => request(`tickets.php?id=${id}&action=asignar`, 'PUT', data),
        stats:       ()            => request('tickets.php?action=stats'),
    },
    usuarios: {
        list:   (params = {}) => request('usuarios.php?' + new URLSearchParams(params)),
        create: (data)        => request('usuarios.php', 'POST', data),
        update: (id, data)    => request(`usuarios.php?id=${id}`, 'PUT', data),
    },
};
