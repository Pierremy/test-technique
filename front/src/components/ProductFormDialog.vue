<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { createProduct, updateProduct } from '@/services/products'

const visible = defineModel({ type: Boolean, default: false })

const props = defineProps({
  // null = création, sinon produit à éditer.
  product: { type: Object, default: null },
  categories: { type: Array, default: () => [] },
})

const emit = defineEmits(['saved'])

const FIELDS = ['name', 'sku', 'category_id', 'price', 'stock']

const emptyForm = () => ({ name: '', sku: '', category_id: null, price: 0, stock: 0 })

const formRef = ref()
const form = reactive(emptyForm())
// Messages de validation renvoyés par l'API (422), affichés sous chaque champ.
const serverErrors = reactive({})
const saving = ref(false)

const isEdit = computed(() => props.product !== null)

const rules = {
  name: [
    { required: true, message: 'Le nom est obligatoire.', trigger: 'blur' },
    { max: 255, message: 'Le nom ne doit pas dépasser 255 caractères.', trigger: 'blur' },
  ],
  sku: [
    { required: true, message: 'Le SKU est obligatoire.', trigger: 'blur' },
    { max: 64, message: 'Le SKU ne doit pas dépasser 64 caractères.', trigger: 'blur' },
    {
      pattern: /^\s*[A-Za-z0-9-]+\s*$/,
      message: 'Lettres, chiffres et tirets uniquement.',
      trigger: 'blur',
    },
  ],
  category_id: [{ required: true, message: 'La catégorie est obligatoire.', trigger: 'change' }],
  price: [{ required: true, message: 'Le prix est obligatoire.', trigger: 'change' }],
  stock: [{ required: true, message: 'Le stock est obligatoire.', trigger: 'change' }],
}

function clearServerErrors() {
  FIELDS.forEach((field) => (serverErrors[field] = ''))
}

// Réinitialise le formulaire à chaque ouverture : vierge en création, pré-rempli en édition.
watch(visible, async (open) => {
  if (!open) return

  const source = props.product ?? emptyForm()
  FIELDS.forEach((field) => (form[field] = source[field]))
  clearServerErrors()

  await nextTick()
  formRef.value?.clearValidate()
})

async function submit() {
  const valid = await formRef.value.validate().catch(() => false)
  if (!valid) return

  saving.value = true
  clearServerErrors()

  try {
    const payload = { ...form }
    const saved = isEdit.value
      ? await updateProduct(props.product.id, payload)
      : await createProduct(payload)

    ElMessage.success(isEdit.value ? 'Produit mis à jour.' : 'Produit créé.')
    emit('saved', saved)
    visible.value = false
  } catch (error) {
    if (error.response?.status === 422) {
      Object.entries(error.response.data.errors ?? {}).forEach(([field, messages]) => {
        serverErrors[field] = messages[0]
      })
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <el-dialog
    v-model="visible"
    :title="isEdit ? 'Modifier le produit' : 'Nouveau produit'"
    width="520px"
    :close-on-click-modal="!saving"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      :disabled="saving"
      @submit.prevent="submit"
    >
      <el-form-item label="Nom" prop="name" :error="serverErrors.name">
        <el-input v-model="form.name" maxlength="255" placeholder="Ex. : Clavier mécanique" />
      </el-form-item>

      <el-form-item label="SKU" prop="sku" :error="serverErrors.sku">
        <el-input v-model="form.sku" maxlength="64" placeholder="Ex. : KEY-00001" />
      </el-form-item>

      <el-form-item label="Catégorie" prop="category_id" :error="serverErrors.category_id">
        <el-select v-model="form.category_id" placeholder="Choisir une catégorie" filterable>
          <el-option
            v-for="category in categories"
            :key="category.id"
            :label="category.name"
            :value="category.id"
          />
        </el-select>
      </el-form-item>

      <div class="form-row">
        <el-form-item label="Prix (€)" prop="price" :error="serverErrors.price">
          <el-input-number
            v-model="form.price"
            :min="0"
            :max="99999999.99"
            :precision="2"
            :step="1"
            controls-position="right"
          />
        </el-form-item>

        <el-form-item label="Stock" prop="stock" :error="serverErrors.stock">
          <el-input-number
            v-model="form.stock"
            :min="0"
            :precision="0"
            :step="1"
            controls-position="right"
          />
        </el-form-item>
      </div>

      <!-- Permet la validation avec la touche Entrée. -->
      <button type="submit" hidden />
    </el-form>

    <template #footer>
      <el-button :disabled="saving" @click="visible = false">Annuler</el-button>
      <el-button type="primary" :loading="saving" @click="submit">
        {{ isEdit ? 'Enregistrer' : 'Créer' }}
      </el-button>
    </template>
  </el-dialog>
</template>

<style scoped>
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.el-select,
.el-input-number {
  width: 100%;
}
</style>
