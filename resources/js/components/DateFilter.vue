<template>
    <div class="p-4 bg-white rounded-lg shadow">
        <h2 class="mb-4 text-lg font-medium">Filtres personnalisés</h2>
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block mb-1 text-sm font-medium text-gray-700">
                    Date de début
                </label>
            <input
                type="date"
                    v-model="filter.debut"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
        </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block mb-1 text-sm font-medium text-gray-700">
                    Date de fin
                </label>
            <input
                type="date"
                    v-model="filter.fin"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
            />
        </div>
            <div class="flex items-end">
                <button
                    @click="applyFilter"
                    class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
            Appliquer
        </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    value: {
        type: Object,
        default: () => ({
            debut: '',
            fin: ''
        })
    }
});

const emit = defineEmits(['filter']);

const filter = ref({
    debut: props.value.debut,
    fin: props.value.fin
});

watch(() => props.value, (newValue) => {
    filter.value = {
        debut: newValue.debut,
        fin: newValue.fin
    };
}, { deep: true });

const applyFilter = () => {
    emit('filter', {
        debut: filter.value.debut,
        fin: filter.value.fin
    });
};
</script>
