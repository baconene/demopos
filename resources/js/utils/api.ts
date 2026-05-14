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

// Axios reads the XSRF-TOKEN cookie and sends it as X-XSRF-TOKEN automatically
// when withCredentials is true — no manual token handling needed.

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
