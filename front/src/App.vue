<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <el-container class="layout">
    <el-header class="layout__header">
      <el-icon :size="22"><Box /></el-icon>
      <span class="layout__title">Catalogue produits</span>
    </el-header>
    <el-main class="layout__main">
      <RouterView />
    </el-main>
  </el-container>
</template>

<style scoped>
.layout {
  min-height: 100vh;
}

.layout__header {
  display: flex;
  align-items: center;
  gap: 10px;
  background: var(--el-bg-color);
  border-bottom: 1px solid var(--el-border-color-light);
}

.layout__title {
  font-size: 18px;
  font-weight: 600;
}

.layout__main {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 16px;
}
</style>
