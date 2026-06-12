<template>
  <div class="space-y-8">
    <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 sm:p-12 text-center">
      <div class="flex justify-center mb-4">
        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>
      <h2 class="text-4xl sm:text-5xl font-bold text-white mb-3">Torrent 资源搜索</h2>
      <p class="text-xl text-white/90">探索海量资源，一键搜索下载</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8">
      <div class="flex gap-3">
        <div class="flex-1">
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="输入电影、剧集、软件等关键词..."
            class="w-full px-6 py-4 text-lg border-2 border-gray-200 rounded-xl focus:border-purple-500 focus:ring-4 focus:ring-purple-200 outline-none transition-all duration-200"
          />
        </div>
        <button
          @click="handleSearch"
          :disabled="loading"
          class="px-8 py-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-purple-800 focus:ring-4 focus:ring-purple-300 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
        >
          <svg v-if="!loading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <svg v-else class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="hidden sm:inline">{{ loading ? '搜索中...' : '搜索' }}</span>
        </button>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <div v-for="i in 5" :key="i" class="bg-white rounded-xl p-6 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-3/4 mb-4"></div>
        <div class="h-4 bg-gray-200 rounded w-1/2 mb-3"></div>
        <div class="flex gap-2">
          <div class="h-8 bg-gray-200 rounded w-20"></div>
          <div class="h-8 bg-gray-200 rounded w-20"></div>
          <div class="h-8 bg-gray-200 rounded w-32"></div>
        </div>
      </div>
    </div>

    <div v-else-if="searchResults.length > 0" class="space-y-4">
      <div
        v-for="result in searchResults"
        :key="result.Magnet || result.Url"
        class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 overflow-hidden"
        :class="{ 'ring-2 ring-red-400': result.has_active_security_issue }"
      >
        <div v-if="result.has_active_security_issue" class="bg-gradient-to-r from-red-500 to-orange-500 px-6 py-2 flex items-center space-x-2">
          <svg class="w-5 h-5 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span class="text-white font-semibold">
            警告：该资源存在 {{ getSeverityText(result.highest_severity) }} 安全漏洞
          </span>
        </div>

        <div v-if="result.security_advisories && result.security_advisories.some(a => a.status === 'resolved')" class="bg-gradient-to-r from-yellow-50 to-orange-50 px-6 py-2 border-b border-yellow-200">
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 0 0118 0z" />
            </svg>
            <span class="text-yellow-800 text-sm font-medium">
              历史公告：该组件曾发生过安全漏洞，请注意风险
            </span>
          </div>
        </div>

        <div class="p-6">
          <div class="flex justify-between items-start gap-4 mb-4">
            <div class="flex-1">
              <h4 class="text-xl font-semibold text-gray-900 mb-3 leading-tight">
                {{ result.Name || result.title }}
              </h4>
              <div class="flex flex-wrap gap-2 mb-3">
                <span v-if="result.Category" class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium whitespace-nowrap">
                  {{ result.Category }}
                </span>
                <span v-if="result.Size" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium whitespace-nowrap flex items-center space-x-1">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span>{{ result.Size }}</span>
                </span>
                <span v-if="result.Version" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium whitespace-nowrap">
                  v{{ result.Version }}
                </span>
              </div>

              <div v-if="result.security_advisories && result.security_advisories.length > 0" class="mb-4 space-y-2">
                <div
                  v-for="advisory in result.security_advisories.slice(0, 2)"
                  :key="advisory.id"
                  class="flex items-center space-x-2 text-sm"
                >
                  <span
                    class="px-2 py-0.5 rounded text-xs font-bold"
                    :class="getSeverityBadgeClass(advisory.severity, advisory.status)"
                  >
                    {{ getSeverityText(advisory.severity) }}
                    {{ advisory.status === 'resolved' ? '(已解除)' : '' }}
                  </span>
                  <span class="text-gray-700 truncate">{{ advisory.title }}</span>
                </div>
                <button
                  v-if="result.security_advisories.length > 2"
                  @click="showDetail(result)"
                  class="text-purple-600 hover:text-purple-700 text-sm font-medium"
                >
                  查看全部 {{ result.security_advisories.length }} 条公告 →
                </button>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 mb-4 p-4 bg-gradient-to-r from-green-50 to-yellow-50 rounded-lg">
            <div class="flex items-center space-x-2 text-green-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <div>
                <div class="text-xs text-gray-600">做种</div>
                <div class="font-bold text-lg">{{ result.Seeders || 0 }}</div>
              </div>
            </div>
            <div class="flex items-center space-x-2 text-yellow-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
              </svg>
              <div>
                <div class="text-xs text-gray-600">下载</div>
                <div class="font-bold text-lg">{{ result.Leechers || 0 }}</div>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3">
            <button
              @click="handleDownload(result)"
              class="flex-1 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
              :class="{ 'from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700': result.has_active_security_issue }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span>{{ result.has_active_security_issue ? '风险下载' : '下载' }}</span>
            </button>
            <button
              @click="copyMagnet(result)"
              class="flex-1 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
              <span>复制磁力链接</span>
            </button>
            <button
              @click="showDetail(result)"
              class="flex-1 py-3 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>详情</span>
            </button>
            <button
              @click="toggleFavorite(result)"
              class="py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-all duration-200"
              :class="{ 'bg-red-100 hover:bg-red-200 text-red-600': isFavorited(result) }"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="hasSearched && searchResults.length === 0" class="text-center py-16">
      <svg class="w-24 h-24 mx-auto text-white/50 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-xl text-white/80">没有找到相关资源</p>
      <p class="text-white/60 mt-2">试试其他关键词吧</p>
    </div>

    <transition name="slide-up">
      <div
        v-if="toast.show"
        class="fixed bottom-8 right-8 px-6 py-4 rounded-lg shadow-2xl text-white font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-500' : toast.type === 'error' ? 'bg-red-500' : toast.type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'"
      >
        {{ toast.message }}
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="detailVisible"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="detailVisible = false"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
          <div
            class="p-6 border-b-2"
            :class="currentResult?.has_active_security_issue ? 'border-red-400 bg-red-50' : 'border-purple-200 bg-purple-50'"
          >
            <div class="flex justify-between items-start">
              <div class="flex-1">
                <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ currentResult?.Name || currentResult?.title }}</h3>
                <div class="flex flex-wrap gap-2">
                  <span v-if="currentResult?.Category" class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-medium">
                    {{ currentResult?.Category }}
                  </span>
                  <span v-if="currentResult?.Size" class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                    {{ currentResult?.Size }}
                  </span>
                  <span v-if="currentResult?.Version" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                    v{{ currentResult?.Version }}
                  </span>
                </div>
              </div>
              <button
                @click="detailVisible = false"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors ml-4"
              >
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div class="p-6 space-y-6">
            <div v-if="currentResult?.security_advisories && currentResult.security_advisories.length > 0">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center space-x-2">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>安全漏洞公告</span>
              </h4>
              <div class="space-y-4">
                <div
                  v-for="advisory in currentResult.security_advisories"
                  :key="advisory.id"
                  class="border-2 rounded-xl p-5 transition-all duration-300"
                  :class="getSeverityBorderClass(advisory.severity, advisory.status)"
                >
                  <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span
                      class="px-3 py-1 rounded-full text-xs font-bold"
                      :class="getSeverityBadgeClass(advisory.severity, advisory.status)"
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
                  <h5 class="text-lg font-bold text-gray-900 mb-3">{{ advisory.title }}</h5>
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                      <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                          <div class="font-medium text-red-800 text-sm">受影响版本</div>
                          <div class="text-red-700 font-bold">{{ advisory.affected_versions }}</div>
                        </div>
                      </div>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                      <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                          <div class="font-medium text-green-800 text-sm">修复版本</div>
                          <div class="text-green-700 font-bold">{{ advisory.fixed_version || '暂无' }}</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="bg-gray-50 rounded-lg p-4 mb-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">漏洞描述</div>
                    <div class="text-gray-600">{{ advisory.description || '暂无详细描述' }}</div>
                  </div>

                  <div v-if="advisory.alternative_resources && advisory.alternative_resources.length > 0" class="mb-4">
                    <div class="text-sm font-medium text-gray-700 mb-2">替代资源/修复方案</div>
                    <ul class="space-y-2">
                      <li
                        v-for="(resource, index) in advisory.alternative_resources"
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

                  <div class="flex items-center justify-between text-sm text-gray-500">
                    <span>发布时间：{{ formatDate(advisory.created_at) }}</span>
                    <span v-if="advisory.status === 'resolved'" class="text-green-600 font-medium">
                      解除时间：{{ formatDate(advisory.resolved_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button
                @click="handleDownload(currentResult)"
                class="flex-1 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
                :class="{ 'from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700': currentResult?.has_active_security_issue }"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ currentResult?.has_active_security_issue ? '风险下载' : '下载' }}</span>
              </button>
              <button
                @click="copyMagnet(currentResult)"
                class="flex-1 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold rounded-lg transition-all duration-200 flex items-center justify-center space-x-2"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <span>复制磁力链接</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade">
      <div
        v-if="securityConfirmVisible"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="securityConfirmVisible = false"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
          <div class="bg-gradient-to-r from-red-500 to-orange-500 p-6 rounded-t-2xl">
            <div class="flex items-center space-x-3">
              <svg class="w-12 h-12 text-white animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <div>
                <h3 class="text-xl font-bold text-white">安全风险警告</h3>
                <p class="text-white/90">该资源存在高危安全漏洞</p>
              </div>
            </div>
          </div>
          
          <div class="p-6 space-y-4">
            <div v-if="pendingDownload?.security_advisories" class="space-y-3">
              <div
                v-for="advisory in pendingDownload.security_advisories.filter(a => a.status === 'active')"
                :key="advisory.id"
                class="bg-red-50 border border-red-200 rounded-lg p-4"
              >
                <div class="flex items-start space-x-2">
                  <span
                    class="px-2 py-0.5 rounded text-xs font-bold flex-shrink-0"
                    :class="getSeverityBadgeClass(advisory.severity, 'active')"
                  >
                    {{ getSeverityText(advisory.severity) }}
                  </span>
                  <div class="flex-1">
                    <div class="font-medium text-red-800">{{ advisory.title }}</div>
                    <div class="text-sm text-red-600 mt-1">影响版本：{{ advisory.affected_versions }}</div>
                    <div class="text-sm text-green-600 mt-1">修复版本：{{ advisory.fixed_version || '暂无' }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <div class="font-medium text-yellow-800">重要提示</div>
                  <div class="text-sm text-yellow-700 mt-1">
                    下载并使用存在已知安全漏洞的版本可能会导致您的系统遭受攻击。
                    强烈建议您升级到已修复的版本或使用替代资源。
                  </div>
                </div>
              </div>
            </div>

            <div v-if="pendingDownload?.security_advisories?.some(a => a.alternative_resources && a.alternative_resources.length > 0)" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="font-medium text-blue-800 mb-2">推荐替代方案</div>
              <ul class="space-y-1">
                <li
                  v-for="(resource, idx) in getAlternativeResources(pendingDownload)"
                  :key="idx"
                  class="text-sm text-blue-700 flex items-start space-x-2"
                >
                  <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>{{ resource }}</span>
                </li>
              </ul>
            </div>

            <div class="flex items-start space-x-3 pt-2">
              <input
                type="checkbox"
                id="confirmRisk"
                v-model="riskConfirmed"
                class="mt-1 w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500"
              />
              <label for="confirmRisk" class="text-sm text-gray-700">
                我已了解上述安全风险，仍要下载该版本。我愿意承担由此带来的一切后果。
              </label>
            </div>
          </div>

          <div class="p-6 border-t border-gray-200 flex gap-3">
            <button
              @click="securityConfirmVisible = false"
              class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors duration-200"
            >
              取消
            </button>
            <button
              @click="confirmDownload"
              :disabled="!riskConfirmed"
              class="flex-1 py-3 bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600 disabled:from-gray-300 disabled:to-gray-400 text-white font-semibold rounded-lg transition-all duration-200 disabled:cursor-not-allowed"
            >
              确认风险下载
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const searchQuery = ref('')
const searchResults = ref([])
const loading = ref(false)
const hasSearched = ref(false)
const toast = ref({ show: false, message: '', type: 'success' })
const detailVisible = ref(false)
const currentResult = ref(null)
const securityConfirmVisible = ref(false)
const pendingDownload = ref(null)
const riskConfirmed = ref(false)
const favorites = ref([])

const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}

const loadFavorites = async () => {
  try {
    const response = await api.getFavorites()
    favorites.value = response.data || []
  } catch (error) {
    console.error('加载收藏失败:', error)
  }
}

const isFavorited = (result) => {
  return favorites.value.some(f => f.magnet === result.Magnet)
}

const toggleFavorite = async (result) => {
  if (isFavorited(result)) {
    const fav = favorites.value.find(f => f.magnet === result.Magnet)
    if (fav) {
      try {
        await api.deleteFavorite(fav.id)
        favorites.value = favorites.value.filter(f => f.id !== fav.id)
        showToast('已取消收藏', 'success')
      } catch (error) {
        showToast('取消收藏失败', 'error')
      }
    }
  } else {
    try {
      await api.addFavorite({
        name: result.Name || result.title,
        magnet: result.Magnet,
        size: result.Size,
        seeders: result.Seeders,
        leechers: result.Leechers,
        category: result.Category,
        source: 'Search'
      })
      await loadFavorites()
      showToast('收藏成功', 'success')
    } catch (error) {
      showToast(error.response?.data?.message || '收藏失败', 'error')
    }
  }
}

const handleSearch = async () => {
  if (!searchQuery.value.trim()) {
    showToast('请输入搜索关键词', 'warning')
    return
  }
  
  loading.value = true
  hasSearched.value = true
  try {
    const response = await api.search('all', searchQuery.value, 1)
    searchResults.value = response.data || []
  } catch (error) {
    showToast('搜索失败，请稍后重试', 'error')
    searchResults.value = []
  } finally {
    loading.value = false
  }
}

const showDetail = (result) => {
  currentResult.value = result
  detailVisible.value = true
}

const handleDownload = (result) => {
  if (result.has_active_security_issue) {
    pendingDownload.value = result
    riskConfirmed.value = false
    securityConfirmVisible.value = true
  } else {
    doDownload(result)
  }
}

const confirmDownload = () => {
  if (riskConfirmed.value && pendingDownload.value) {
    doDownload(pendingDownload.value)
    securityConfirmVisible.value = false
    pendingDownload.value = null
  }
}

const doDownload = (result) => {
  if (result.Magnet) {
    window.location.href = result.Magnet
    showToast('已开始下载', 'success')
  } else if (result.Url) {
    window.open(result.Url, '_blank')
    showToast('已打开下载页面', 'success')
  } else {
    showToast('无法获取下载链接', 'error')
  }
}

const copyMagnet = (result) => {
  if (result.Magnet) {
    navigator.clipboard.writeText(result.Magnet)
    showToast('磁力链接已复制到剪贴板', 'success')
  } else {
    showToast('没有可用的磁力链接', 'error')
  }
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

const getSeverityBadgeClass = (severity, status) => {
  if (status === 'resolved') {
    return 'bg-gray-400 text-white'
  }
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
    return 'border-gray-300 bg-gray-50/50'
  }
  const map = {
    critical: 'border-red-400 bg-red-50/50',
    high: 'border-orange-400 bg-orange-50/50',
    medium: 'border-yellow-400 bg-yellow-50/50',
    low: 'border-blue-400 bg-blue-50/50'
  }
  return map[severity] || 'border-gray-300'
}

const getAlternativeResources = (result) => {
  if (!result?.security_advisories) return []
  const resources = []
  result.security_advisories.forEach(advisory => {
    if (advisory.alternative_resources && advisory.alternative_resources.length > 0) {
      resources.push(...advisory.alternative_resources)
    }
  })
  return [...new Set(resources)]
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
  loadFavorites()
  if (route.query.query) {
    searchQuery.value = route.query.query
    handleSearch()
  }
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
