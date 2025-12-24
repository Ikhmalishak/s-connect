<template>
    <div class="flex items-center gap-2">
        <label for="language-select" class="text-sm font-medium text-gray-700">
            {{ $t('language.select') }}:
        </label>
        <select
            id="language-select"
            :value="currentLanguage"
            @change="changeLanguage"
            class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
            <option value="en">{{ $t('language.english') }}</option>
            <option value="zh">{{ $t('language.chinese') }}</option>
            <option value="ms">{{ $t('language.malay') }}</option>
        </select>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { locale } = useI18n()
const currentLanguage = ref('en')

const changeLanguage = (event: Event) => {
    const target = event.target as HTMLSelectElement
    const newLang = target.value
    locale.value = newLang
    localStorage.setItem('visitor-language', newLang)
    currentLanguage.value = newLang
}

onMounted(() => {
    currentLanguage.value = locale.value
})
</script>
