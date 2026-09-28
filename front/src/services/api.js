import axios from 'axios'
import { ElMessage } from 'element-plus'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

// Notification centralisée des erreurs. Les erreurs de validation (422) sont
// affichées sous les champs par les formulaires, pas ici.
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (axios.isCancel(error)) {
      return Promise.reject(error)
    }

    const status = error.response?.status

    if (!error.response) {
      ElMessage.error("Impossible de joindre l'API.")
    } else if (status === 404) {
      ElMessage.error('Ressource introuvable.')
    } else if (status !== 422) {
      ElMessage.error('Une erreur est survenue, veuillez réessayer.')
    }

    return Promise.reject(error)
  },
)

export default api
