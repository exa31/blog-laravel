import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;

let isRefreshing = false;
let failedQueue = [];

const processQueue = (error) => {
    failedQueue.forEach((prom) => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve();
        }
    });
    failedQueue = [];
};

axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        if (
            error.response &&
            (error.response.status === 401 || error.response.status === 419) &&
            !originalRequest._retry &&
            !originalRequest.url?.includes('/login') &&
            !originalRequest.url?.includes('/register') &&
            !originalRequest.url?.includes('/refresh')
        ) {
            if (isRefreshing) {
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                })
                    .then(() => axios(originalRequest))
                    .catch((err) => Promise.reject(err));
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                await axios.post('/refresh');
                processQueue(null);
                return axios(originalRequest);
            } catch (refreshErr) {
                processQueue(refreshErr);
                return Promise.reject(refreshErr);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);
