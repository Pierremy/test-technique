<script setup>
import { onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Delete, Edit, Plus, Search } from '@element-plus/icons-vue'
import ProductFormDialog from '@/components/ProductFormDialog.vue'
import { fetchCategories } from '@/services/categories'
import { deleteProduct, fetchProducts } from '@/services/products'

const SEARCH_DEBOUNCE_MS = 300

const products = ref([])
const categories = ref([])
const total = ref(0)
const loading = ref(false)

const filters = reactive({ search: '', category_id: null, in_stock: null })
const sort = reactive({ sort: null, direction: null })
const pagination = reactive({ page: 1, perPage: 15 })

const dialogVisible = ref(false)
const editedProduct = ref(null)

const priceFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' })

let abortController = null
let searchTimer = null

async function loadProducts() {
  // Annule la requête précédente : seule la réponse la plus récente doit s'afficher.
  abortController?.abort()
  abortController = new AbortController()

  loading.value = true

  try {
    const response = await fetchProducts(
      {
        page: pagination.page,
        per_page: pagination.perPage,
        search: filters.search.trim() || undefined,
        category_id: filters.category_id ?? undefined,
        in_stock: filters.in_stock ?? undefined,
        sort: sort.sort ?? undefined,
        direction: sort.direction ?? undefined,
      },
      { signal: abortController.signal },
    )

    products.value = response.data
    total.value = response.meta.total
    loading.value = false
  } catch (error) {
    // Une requête annulée a été remplacée par une plus récente, qui gère le chargement.
    if (!axios.isCancel(error)) {
      loading.value = false
    }
  }
}

async function loadCategories() {
  try {
    categories.value = await fetchCategories()
  } catch {
    // Erreur déjà notifiée par l'intercepteur ; le formulaire restera sans catégories.
  }
}

// Tout changement de filtre repart de la première page.
function applyFilters() {
  pagination.page = 1
  loadProducts()
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, SEARCH_DEBOUNCE_MS)
}

function onSortChange({ prop, order }) {
  sort.sort = order ? prop : null
  sort.direction = order === 'ascending' ? 'asc' : order === 'descending' ? 'desc' : null
  applyFilters()
}

function onPageSizeChange() {
  applyFilters()
}

function openCreate() {
  editedProduct.value = null
  dialogVisible.value = true
}

function openEdit(product) {
  editedProduct.value = product
  dialogVisible.value = true
}

async function confirmDelete(product) {
  try {
    await ElMessageBox.confirm(
      `Supprimer le produit « ${product.name} » (${product.sku}) ? Cette action est irréversible.`,
      'Confirmer la suppression',
      {
        type: 'warning',
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonClass: 'el-button--danger',
      },
    )
  } catch {
    return // Suppression annulée.
  }

  try {
    await deleteProduct(product.id)
    ElMessage.success('Produit supprimé.')

    // Si l'on vient de vider la dernière page, on recule d'une page.
    if (products.value.length === 1 && pagination.page > 1) {
      pagination.page--
    }
    await loadProducts()
  } catch {
    // Erreur déjà notifiée par l'intercepteur.
  }
}

onMounted(() => {
  loadCategories()
  loadProducts()
})
</script>

<template>
  <section class="products">
    <header class="products__header">
      <div>
        <h1>Produits</h1>
        <p class="products__count">{{ total }} produit{{ total > 1 ? 's' : '' }}</p>
      </div>
      <el-button type="primary" :icon="Plus" @click="openCreate">Nouveau produit</el-button>
    </header>

    <div class="products__toolbar">
      <el-input
        v-model="filters.search"
        class="products__search"
        placeholder="Rechercher par nom ou SKU"
        :prefix-icon="Search"
        clearable
        @input="onSearchInput"
      />
      <el-select
        v-model="filters.category_id"
        class="products__filter"
        placeholder="Toutes les catégories"
        clearable
        filterable
        @change="applyFilters"
      >
        <el-option
          v-for="category in categories"
          :key="category.id"
          :label="category.name"
          :value="category.id"
        />
      </el-select>
      <el-select
        v-model="filters.in_stock"
        class="products__filter"
        placeholder="Tous les stocks"
        clearable
        @change="applyFilters"
      >
        <el-option label="En stock" :value="1" />
        <el-option label="En rupture" :value="0" />
      </el-select>
    </div>

    <el-table
      v-loading="loading"
      :data="products"
      row-key="id"
      border
      @sort-change="onSortChange"
    >
      <el-table-column prop="name" label="Nom" min-width="220" sortable="custom" />
      <el-table-column prop="sku" label="SKU" width="150" sortable="custom" />
      <el-table-column label="Catégorie" min-width="140">
        <template #default="{ row }">{{ row.category?.name }}</template>
      </el-table-column>
      <el-table-column prop="price" label="Prix" width="130" align="right" sortable="custom">
        <template #default="{ row }">{{ priceFormatter.format(row.price) }}</template>
      </el-table-column>
      <el-table-column prop="stock" label="Stock" width="120" align="right" sortable="custom">
        <template #default="{ row }">
          <el-tag v-if="row.stock === 0" type="danger" size="small">Rupture</el-tag>
          <span v-else>{{ row.stock }}</span>
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="200" align="center">
        <template #default="{ row }">
          <el-button link type="primary" :icon="Edit" @click="openEdit(row)">Modifier</el-button>
          <el-button link type="danger" :icon="Delete" @click="confirmDelete(row)">
            Supprimer
          </el-button>
        </template>
      </el-table-column>

      <template #empty>Aucun produit ne correspond à votre recherche.</template>
    </el-table>

    <el-pagination
      v-model:current-page="pagination.page"
      v-model:page-size="pagination.perPage"
      class="products__pagination"
      :total="total"
      :page-sizes="[10, 15, 25, 50, 100]"
      layout="total, sizes, prev, pager, next"
      background
      @current-change="loadProducts"
      @size-change="onPageSizeChange"
    />

    <ProductFormDialog
      v-model="dialogVisible"
      :product="editedProduct"
      :categories="categories"
      @saved="loadProducts"
    />
  </section>
</template>

<style scoped>
.products {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.products__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.products__header h1 {
  font-size: 24px;
  font-weight: 600;
}

.products__count {
  color: var(--el-text-color-secondary);
}

.products__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.products__search {
  flex: 1 1 280px;
}

.products__filter {
  flex: 0 1 220px;
}

.products__pagination {
  justify-content: flex-end;
}
</style>
