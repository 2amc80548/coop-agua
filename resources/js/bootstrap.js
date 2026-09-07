import axios from 'axios';
window.axios = axios;

// Enviar siempre la cookie de sesión de Laravel (necesario para rutas protegidas)
window.axios.defaults.withCredentials = true;

// Header identificador de AJAX requerido por Laravel
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Token CSRF automático desde la meta tag (protege todos los POST/PUT/DELETE)
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
}
