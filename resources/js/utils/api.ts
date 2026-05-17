import axios from 'axios'

const api = axios.create({
    baseURL: window.location.origin,
    withCredentials: true, // required for Sanctum session-based auth
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
})

api.interceptors.request.use((config) => {
    if (config.data instanceof FormData) {
        delete config.headers['Content-Type']
    }
    return config
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

export default api
