<template>
    <select
        :value="currentLanguage"
        @change="changeLanguage"
        class="border border-gray-300 p-2 rounded-md max-w-xs w-full text-center text-lg"
    >
        <option value="en">{{ $t('language.english') }}</option>
        <option value="zh">{{ $t('language.chinese') }}</option>
        <option value="ms">{{ $t('language.malay') }}</option>
    </select>
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
    locale.value = 'en'
    currentLanguage.value = 'en'
})
</script>
