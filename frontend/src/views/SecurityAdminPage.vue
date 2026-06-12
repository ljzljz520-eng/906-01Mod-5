<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-16 h-16 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
      </div>
      <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">安全漏洞管理</h2>
      <p class="text-lg text-white/90">管理开源组件安全漏洞公告，保护用户系统安全</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h3 class="text-xl font-bold text-gray-900">安全公告列表</h3>
        <button
          @click="openCreateModal"
          class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-semibold rounded-xl transition-all duration-200 flex items-center space-x-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>新增漏洞公告</span>
        </button>
      </div>

      <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1">
          <input
            v-model="searchKeyword"
            @keyup.enter="loadAdvisories"
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
          class="border-2 rounded-xl p-6 transition-all duration-300 hover:shadow-lg"
          :class="getSeverityBorderClass(advisory.severity, advisory.status)"
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
              <h3 class="text-xl font-bold text-gray-900 mb-2">{{ advisory.title }}</h3>
            </div>
            <div class="flex gap-2 flex-shrink-0">
              <button
                @click="editAdvisory(advisory)"
                class="p-2 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg transition-colors"
                title="编辑"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                v-if="advisory.status === 'active'"
                @click="resolveAdvisory(advisory)"
                class="p-2 bg-green-100 hover:bg-green-200 text-green-600 rounded-lg transition-colors"
                title="标记为已解除"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </button>
              <button
                v-else
                @click="reactivateAdvisory(advisory)"
                class="p-2 bg-yellow-100 hover:bg-yellow-200 text-yellow-600 rounded-lg transition-colors"
                title="重新激活"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </button>
              <button
                @click="confirmDelete(advisory)"
                class="p-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition-colors"
                title="删除"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-4">
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

          <div v-if="advisory.status === 'resolved'" class="bg-green-50 rounded-lg p-3 text-sm">
            <div class="flex items-center space-x-2 text-green-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>漏洞已于 {{ formatDate(advisory.resolved_at) }} 解除，保留公告历史供用户参考</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-16">
        <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-xl text-gray-500">暂无安全公告</p>
        <button
          @click="openCreateModal"
          class="mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors"
        >
          添加第一条公告
        </button>
      </div>
    </div>

    <transition name="fade">
      <div
        v-if="modalVisible"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="modalVisible = false"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="p-6 border-b-2 border-purple-200 bg-purple-50">
            <div class="flex justify-between items-center">
              <h3 class="text-2xl font-bold text-gray-900">
                {{ editingId ? '编辑漏洞公告' : '新增漏洞公告' }}
              </h3>
              <button
                @click="modalVisible = false"
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
              <label class="block text-sm font-medium text-gray-700 mb-2">组件名称 *</label>
              <input
                v-model="formData.component_name"
                type="text"
                placeholder="例如：log4j, openssl, struts2"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">漏洞标题 *</label>
              <input
                v-model="formData.title"
                type="text"
                placeholder="例如：Log4j2 JNDI 注入漏洞 (CVE-2021-44228)"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">严重程度 *</label>
                <select
                  v-model="formData.severity"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
                >
                  <option value="critical">严重 (Critical)</option>
                  <option value="high">高危 (High)</option>
                  <option value="medium">中危 (Medium)</option>
                  <option value="low">低危 (Low)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">状态</label>
                <select
                  v-model="formData.status"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
                >
                  <option value="active">活跃中</option>
                  <option value="resolved">已解除</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">受影响版本 *</label>
                <input
                  v-model="formData.affected_versions"
                  type="text"
                  placeholder="例如：2.0-beta9 到 2.14.1"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">修复版本</label>
                <input
                  v-model="formData.fixed_version"
                  type="text"
                  placeholder="例如：2.17.1"
                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">漏洞描述</label>
              <textarea
                v-model="formData.description"
                rows="4"
                placeholder="详细描述漏洞的原理、影响范围和攻击方式..."
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200 resize-none"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                替代资源/修复方案
                <span class="text-gray-400 font-normal">（每行一个）</span>
              </label>
              <textarea
                v-model="alternativeResourcesText"
                rows="4"
                placeholder="升级到 Log4j 2.17.1 或更高版本&#10;使用 Logback 作为替代日志框架"
                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200 resize-none"
              ></textarea>
            </div>
          </div>

          <div class="p-6 border-t border-gray-200 flex gap-3">
            <button
              @click="modalVisible = false"
              class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors duration-200"
            >
              取消
            </button>
            <button
              @click="saveAdvisory"
              :disabled="saving || !isFormValid"
              class="flex-1 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 disabled:from-gray-300 disabled:to-gray-400 text-white font-semibold rounded-xl transition-all duration-200 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
            >
              <svg v-if="saving" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ saving ? '保存中...' : (editingId ? '更新公告' : '创建公告') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="confirmDialogVisible"
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 px-4"
        @click.self="confirmDialogVisible = false"
      >
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
          <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ confirmDialog.title }}</h3>
          <p class="text-gray-600 mb-6">{{ confirmDialog.message }}</p>
          <div class="flex gap-3">
            <button
              @click="confirmDialogVisible = false"
              class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors duration-200"
            >
              取消
            </button>
            <button
              @click="executeConfirmAction"
              :class="confirmDialog.buttonClass || 'bg-red-500 hover:bg-red-600'"
              class="flex-1 px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200"
            >
              {{ confirmDialog.buttonText || '确认' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-2xl text-white font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
      >
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'

const advisories = ref([])
const loading = ref(false)
const saving = ref(false)
const searchKeyword = ref('')
const statusFilter = ref('')
const severityFilter = ref('')
const modalVisible = ref(false)
const editingId = ref(null)
const confirmDialogVisible = ref(false)
const confirmDialog = ref({ title: '', message: '', action: null, buttonText: '', buttonClass: '' })
const toast = ref({ show: false, message: '', type: 'success' })

const formData = ref({
  component_name: '',
  affected_versions: '',
  fixed_version: '',
  severity: 'high',
  title: '',
  description: '',
  status: 'active'
})

const alternativeResourcesText = ref('')

const isFormValid = computed(() => {
  return formData.value.component_name.trim() && 
         formData.value.title.trim() && 
         formData.value.affected_versions.trim()
})

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const loadAdvisories = async () => {
  loading.value = true
  try {
    if (searchKeyword.value.trim()) {
      const response = await api.matchSecurityAdvisories(searchKeyword.value)
      let data = response.data || []
      if (statusFilter.value) {
        data = data.filter(a => a.status === statusFilter.value)
      }
      if (severityFilter.value) {
        data = data.filter(a => a.severity === severityFilter.value)
      }
      advisories.value = data
    } else {
      const params = {}
      if (statusFilter.value) params.status = statusFilter.value
      if (severityFilter.value) params.severity = severityFilter.value
      const response = await api.getSecurityAdvisories(params)
      advisories.value = response.data || []
    }
  } catch (error) {
    showToast('加载安全公告失败', 'error')
  } finally {
    loading.value = false
  }
}

const openCreateModal = () => {
  editingId.value = null
  formData.value = {
    component_name: '',
    affected_versions: '',
    fixed_version: '',
    severity: 'high',
    title: '',
    description: '',
    status: 'active'
  }
  alternativeResourcesText.value = ''
  modalVisible.value = true
}

const editAdvisory = (advisory) => {
  editingId.value = advisory.id
  formData.value = {
    component_name: advisory.component_name,
    affected_versions: advisory.affected_versions,
    fixed_version: advisory.fixed_version || '',
    severity: advisory.severity,
    title: advisory.title,
    description: advisory.description || '',
    status: advisory.status
  }
  alternativeResourcesText.value = advisory.alternative_resources 
    ? advisory.alternative_resources.join('\n') 
    : ''
  modalVisible.value = true
}

const saveAdvisory = async () => {
  if (!isFormValid.value) return
  
  saving.value = true
  try {
    const alternatives = alternativeResourcesText.value
      .split('\n')
      .map(line => line.trim())
      .filter(line => line.length > 0)
    
    const data = {
      ...formData.value,
      alternative_resources: alternatives
    }
    
    if (editingId.value) {
      await api.updateSecurityAdvisory(editingId.value, data)
      showToast('安全公告更新成功', 'success')
    } else {
      await api.createSecurityAdvisory(data)
      showToast('安全公告创建成功', 'success')
    }
    
    modalVisible.value = false
    loadAdvisories()
  } catch (error) {
    showToast(error.response?.data?.message || '保存失败', 'error')
  } finally {
    saving.value = false
  }
}

const resolveAdvisory = (advisory) => {
  confirmDialog.value = {
    title: '标记为已解除',
    message: `确定要将 "${advisory.title}" 标记为已解除吗？解除后公告仍会保留，用户可以看到历史记录。`,
    buttonText: '确认解除',
    buttonClass: 'bg-green-500 hover:bg-green-600',
    action: async () => {
      try {
        await api.updateSecurityAdvisoryStatus(advisory.id, 'resolved')
        showToast('漏洞已解除，公告历史已保留', 'success')
        loadAdvisories()
      } catch (error) {
        showToast('操作失败', 'error')
      }
    }
  }
  confirmDialogVisible.value = true
}

const reactivateAdvisory = (advisory) => {
  confirmDialog.value = {
    title: '重新激活公告',
    message: `确定要重新激活 "${advisory.title}" 吗？这会将状态改回活跃中，用户下载时会再次收到风险提示。`,
    buttonText: '确认激活',
    buttonClass: 'bg-yellow-500 hover:bg-yellow-600',
    action: async () => {
      try {
        await api.updateSecurityAdvisoryStatus(advisory.id, 'active')
        showToast('公告已重新激活', 'success')
        loadAdvisories()
      } catch (error) {
        showToast('操作失败', 'error')
      }
    }
  }
  confirmDialogVisible.value = true
}

const confirmDelete = (advisory) => {
  confirmDialog.value = {
    title: '删除公告',
    message: `确定要删除 "${advisory.title}" 吗？此操作不可恢复。建议仅在公告录入错误时删除，正常解除请使用"标记为已解除"功能以保留历史记录。`,
    buttonText: '确认删除',
    buttonClass: 'bg-red-500 hover:bg-red-600',
    action: async () => {
      try {
        await api.deleteSecurityAdvisory(advisory.id)
        showToast('公告已删除', 'success')
        loadAdvisories()
      } catch (error) {
        showToast('删除失败', 'error')
      }
    }
  }
  confirmDialogVisible.value = true
}

const executeConfirmAction = () => {
  if (confirmDialog.value.action) {
    confirmDialog.value.action()
  }
  confirmDialogVisible.value = false
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
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
