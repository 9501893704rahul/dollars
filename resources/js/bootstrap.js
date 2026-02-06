import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Breeze puts this in layouts/app.blade.php
const tokenTag = document.head.querySelector('meta[name="csrf-token"]');
if (tokenTag) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenTag.content;
}

/**
 * Simple API helper: api.get/post/put/patch/delete
 * Uses full URLs (no baseURL magic)
 */
window.api = {
    get(url, config = {}) {
        return axios.get(url, config).then(res => res.data);
    },
    post(url, data = {}, config = {}) {
        return axios.post(url, data, config).then(res => res.data);
    },
    put(url, data = {}, config = {}) {
        return axios.put(url, data, config).then(res => res.data);
    },
    patch(url, data = {}, config = {}) {
        return axios.patch(url, data, config).then(res => res.data);
    },
    delete(url, config = {}) {
        return axios.delete(url, config).then(res => res.data);
    },
};
