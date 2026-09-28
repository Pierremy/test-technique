import api from './api'

/**
 * Liste paginée. Renvoie la réponse Laravel complète : { data, links, meta }.
 */
export async function fetchProducts(params, { signal } = {}) {
  const { data } = await api.get('/products', { params, signal })
  return data
}

export async function createProduct(payload) {
  const { data } = await api.post('/products', payload)
  return data.data
}

export async function updateProduct(id, payload) {
  const { data } = await api.put(`/products/${id}`, payload)
  return data.data
}

export async function deleteProduct(id) {
  await api.delete(`/products/${id}`)
}
