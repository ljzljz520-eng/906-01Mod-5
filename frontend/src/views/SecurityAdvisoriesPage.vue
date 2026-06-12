<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-16 h-16 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">安全漏洞公告</h2>
      <p class="text-lg text-white/90">及时了解开源组件安全风险，保护您的系统安全</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-6">
      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1">
          <input
            v-model="searchKeyword"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="搜索组件名称或漏洞标题..."
            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
          />
        </div>
        <select
          v-model="statusFilter"
          @change="loadAdvisories"
          class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
        >
          <option value="">全部状态</option>
          <option value="active">活跃漏洞</option>
          <option value="resolved">已解除</option>
        </select>
        <select
          v-model="severityFilter"
          @change="loadAdvisories"
          class="px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
        >
          <option value="">全部等级</option>
          <option value="critical">严重</option>
          <option value="high">高危</option>
          <option value="medium">中危</option>
          <option value="low">低危</option>
        </select>
      </div>

      <div v-if="loading" class="space-y-4">
        <div v-for="i in 3" :key="i" class="animate-pulse">
          <div class="h-32 bg-gray-200 rounded-xl"></div>
        </div>
      </div>

      <div v-else-if="advisories.length > 0" class="space-y-4">
        <div
          v-for="advisory in advisories"
          :key="advisory.id"
          class="border-2 rounded-xl p-6 transition-all duration-300 hover:shadow-lg cursor-pointer"
          :class="getSeverityBorderClass(advisory.severity, advisory.status)"
          @click="showDetail(advisory)"
        >
          <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
            <div class="flex-1">
              <div class="flex flex-wrap items-center gap-2 mb-2">
                <span
                  class="px-3 py-1 rounded-full text-xs font-bold"
                  :class="getSeverityBadgeClass(advisory.severity)"
                >
                  {{ getSeverityText(advisory.severity) }}
                </span>
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="advisory.status === 'active' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                >
                  {{ advisory.status === 'active' ? '活跃中' : '已解除' }}
                </span>
                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium">
                  {{ advisory.component_name }}
                </span>
              </div>
              <h3 class="text-xl font-bold text-gray-900">{{ advisory.title }}</h3>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="text-gray-500 mb-1">受影响版本</div>
              <div class="font-medium text-gray-900">{{ advisory.affected_versions }}</div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
              <div class="text-gray-500 mb-1">修复版本</div>
              <div class="font-medium text-green-700">{{ advisory.fixed_version || '暂无' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="text-gray-500 mb-1">发布时间</div>
              <div class="font-medium text-gray-900">{{ formatDate(advisory.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xl text-gray-500">暂无安全公告</p>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="detailVisible"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="detailVisible = false"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div
            class="p-6 border-b-2"
            :class="getSeverityBorderClass(currentAdvisory.severity, currentAdvisory.status)"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-3">
                  <span
                    class="px-3 py-1 rounded-full text-xs font-bold"
                    :class="getSeverityBadgeClass(currentAdvisory.severity)"
                  >
                    {{ getSeverityText(currentAdvisory.severity) }}
                  </span>
                  <span
                    class="px-3 py-1 rounded-full text-xs font-medium"
                    :class="currentAdvisory.status === 'active' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'"
                  >
                    {{ currentAdvisory.status === 'active' ? '活跃中' : '已解除' }}
                  </span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">{{ currentAdvisory.title }}</h3>
              </div>
              <button
                @click="detailVisible = false"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors"
              >
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-6 space-y-6">
            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">组件信息</h4>
              <div class="grid grid-cols-2 gap-4">
                <div class="bg-purple-50 rounded-lg p-4">
                  <div class="text-sm text-purple-600 mb-1">组件名称</div>
                  <div class="font-bold text-purple-900">{{ currentAdvisory.component_name }}</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="text-sm text-gray-600 mb-1">发布时间</div>
                  <div class="font-bold text-gray-900">{{ formatDate(currentAdvisory.created_at) }}</div>
                </div>
              </div>
            </div>

            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">影响范围</h4>
              <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                  <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <div>
                    <div class="font-medium text-red-800">受影响版本</div>
                    <div class="text-red-700">{{ currentAdvisory.affected_versions }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">修复方案</h4>
              <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start space-x-3">
                  <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <div class="font-medium text-green-800">修复版本</div>
                    <div class="text-green-700">{{ currentAdvisory.fixed_version || '暂无修复版本' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <h4 class="text-lg font-semibold text-gray-900 mb-2">漏洞描述</h4>
              <div class="bg-gray-50 rounded-lg p-4 text-gray-700 leading-relaxed">
                {{ currentAdvisory.description || '暂无详细描述' }}
              </div>
            </div>

            <div v-if="currentAdvisory.alternative_resources && currentAdvisory.alternative_resources.length > 0">
              <h4 class="text-lg font-semibold text-gray-900 mb-2">替代资源</h4>
              <ul class="space-y-2">
                <li
                  v-for="(resource, index) in currentAdvisory.alternative_resources"
                  :key="index"
                  class="flex items-start space-x-3 bg-blue-50 rounded-lg p-3"
                >
                  <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                  </svg>
                  <span class="text-blue-700">{{ resource }}</span>
                </li>
              </ul>
            </div>

            <div v-if="currentAdvisory.status === 'resolved'" class="bg-gray-100 rounded-lg p-4">
              <div class="flex items-center space-x-3">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-gray-600">漏洞已于 {{ formatDate(currentAdvisory.resolved_at) }} 解除</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const advisories = ref([])
const loading = ref(false)
const searchKeyword = ref('')
const statusFilter = ref('')
const severityFilter = ref('')
const detailVisible = ref(false)
const currentAdvisory = ref(null)

const loadAdvisories = async () => {
  loading.value = true
  try {
    const params = {}
    if (statusFilter.value) params.status = statusFilter.value
    if (severityFilter.value) params.severity = severityFilter.value
    
    const response = await api.getSecurityAdvisories(params)
    advisories.value = response.data || []
  } catch (error) {
    console.error('加载安全公告失败:', error)
  } finally {
    loading.value = false
  }
}

const handleSearch = async () => {
  if (!searchKeyword.value.trim()) {
    loadAdvisories()
    return
  }
  
  loading.value = true
  try {
    const response = await api.matchSecurityAdvisories(searchKeyword.value)
    advisories.value = response.data || []
  } catch (error) {
    console.error('搜索安全公告失败:', error)
  } finally {
    loading.value = false
  }
}

const showDetail = (advisory) => {
  currentAdvisory.value = advisory
  detailVisible.value = true
}

const getSeverityText = (severity) => {
  const map = {
    critical: '严重',
    high: '高危',
    medium: '中危',
    low: '低危'
  }
  return map[severity] || severity
}

const getSeverityBadgeClass = (severity) => {
  const map = {
    critical: 'bg-red-600 text-white',
    high: 'bg-orange-500 text-white',
    medium: 'bg-yellow-500 text-white',
    low: 'bg-blue-500 text-white'
  }
  return map[severity] || 'bg-gray-500 text-white'
}

const getSeverityBorderClass = (severity, status) => {
  if (status === 'resolved') {
    return 'border-green-300 bg-green-50/30'
  }
  const map = {
    critical: 'border-red-400 bg-red-50/50',
    high: 'border-orange-400 bg-orange-50/50',
    medium: 'border-yellow-400 bg-yellow-50/50',
    low: 'border-blue-400 bg-blue-50/50'
  }
  return map[severity] || 'border-gray-300'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '未知'
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  })
}

onMounted(() => {
  loadAdvisories()
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
