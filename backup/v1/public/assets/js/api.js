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
        login:    (email, password) => request('auth.php?action=login', 'POST', { email, password }),
        register: (data)            => request('auth.php?action=register', 'POST', data),
        logout:   ()                => request('auth.php?action=logout', 'POST'),
        me:       ()                => request('auth.php?action=me'),
        passwordReset:         (email) => request('auth.php?action=password-reset', 'POST', { email }),
        passwordResetConfirm:  (token, password) => request('auth.php?action=password-reset-confirm', 'POST', { token, password }),
    },
    tickets: {
        list:        (params = {}) => request('tickets.php?' + new URLSearchParams(params)),
        get:         (id)          => request(`tickets.php?id=${id}`),
        create:      (data)        => request('tickets.php', 'POST', data),
        updateEstado:(id, data)    => request(`tickets.php?id=${id}&action=estado`, 'PUT', data),
        asignar:     (id, data)    => request(`tickets.php?id=${id}&action=asignar`, 'PUT', data),
        updateAsunto:(id, data)    => request(`tickets.php?id=${id}&action=asunto`, 'PUT', data),
        stats:       ()            => request('tickets.php?action=stats'),
        responder:   (id, data)    => request(`tickets.php?id=${id}&action=responder`, 'POST', data),
        timeline:    (id)          => request(`tickets.php?id=${id}&action=timeline`),
    },
    upload: {
        subir:  (ticketId, file) => { const fd = new FormData(); fd.append('ticket_id', ticketId); fd.append('file', file); return fetch(BASE + '/upload.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(r => r.json()); },
        eliminar: (id) => request('upload.php?id=' + id, 'DELETE'),
    },
    usuarios: {
        list:   (params = {}) => request('usuarios.php?' + new URLSearchParams(params)),
        create: (data)        => request('usuarios.php', 'POST', data),
        update: (id, data)    => request(`usuarios.php?id=${id}`, 'PUT', data),
        delete: (id)          => request(`usuarios.php?id=${id}`, 'DELETE'),
    },
    dashboard: {
        metrics: ()          => request('dashboard.php?action=metrics'),
        pending: (params={}) => request('dashboard.php?action=pending&' + new URLSearchParams(params)),
    },
    catalogos: {
        sitios:         ()       => request('catalogos.php?type=sitios'),
        lineas:         ()       => request('catalogos.php?type=lineas'),
        tiposSolicitud: ()       => request('catalogos.php?type=tipos_solicitud'),
        tiposAsunto:    ()       => request('catalogos.php?type=tipos_asunto'),
        asuntos:        (tipoId) => request('catalogos.php?type=asuntos' + (tipoId ? '&tipo_asunto_id=' + tipoId : '')),
        usuarios:       (params) => request('catalogos.php?type=usuarios' + (params ? '&' + new URLSearchParams(params) : '')),
        lugarIncidencia: ()    => request('catalogos.php?type=lugar_incidencia'),
        regiones:       ()       => request('catalogos.php?type=regiones'),
        // CRUD genérico
        list:   (type)   => request(`catalogos.php?type=${type}`),
        create: (type, data) => request(`catalogos.php?type=${type}`, 'POST', data),
        update: (type, id, data) => request(`catalogos.php?type=${type}&id=${id}`, 'PUT', data),
        delete: (type, id) => request(`catalogos.php?type=${type}&id=${id}`, 'DELETE'),
    },
};
