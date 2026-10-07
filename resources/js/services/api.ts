import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

// Attach token from localStorage if exists
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('lp_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    return config;
});

export default api;
