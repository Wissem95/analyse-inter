<template>
    <div class="card">
        <h2 class="mb-4 text-lg font-medium text-gray-900">Upload de fichier</h2>

        <!-- Zone de drop -->
        <div class="space-y-4">
            <div
                class="flex items-center justify-center w-full"
                @dragover.prevent="dragover = true"
                @dragleave.prevent="dragover = false"
                @drop.prevent="handleDrop"
            >
                <label
                    :class="[
                        'flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-lg cursor-pointer transition-colors',
                        dragover ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100'
                    ]"
                >
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg
                            class="w-8 h-8 mb-4 text-gray-500"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>
                        <p class="mb-2 text-sm text-gray-500">
                            <span class="font-semibold">Cliquez pour uploader</span>
                            ou glissez-déposez
                        </p>
                        <p class="text-xs text-gray-500">Excel ou CSV (MAX. 16MB)</p>
                    </div>
                    <input
                        type="file"
                        class="hidden"
                        accept=".xlsx,.xls,.csv"
                        @change="handleFileSelect"
                    />
                </label>
            </div>
        </div>

        <!-- Message de progression -->
        <div v-if="uploading || success || error" class="mt-4">
            <div v-if="uploading" class="text-blue-600">
                Upload en cours...
            </div>
            <div v-if="success" class="text-green-600">
                {{ success }}
            </div>
            <div v-if="error" class="text-red-600">
                {{ error }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const dragover = ref(false);
const uploading = ref(false);
const success = ref('');
const error = ref('');

const emit = defineEmits(['upload-complete']);

const uploadFile = async (file) => {
    const formData = new FormData();
    formData.append('file', file);

    uploading.value = true;
    success.value = '';
    error.value = '';

    try {
        const response = await fetch('/api/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Vérifier d'abord le Content-Type
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Le serveur n\'a pas renvoyé une réponse JSON valide');
        }

        const data = await response.json();

        if (!response.ok) {
            if (response.status === 422 && data.errors) {
                error.value = Object.values(data.errors)
                    .flat()
                    .join('\n');
            } else {
                error.value = data.message || 'Erreur lors de l\'upload';
            }
            return;
        }

        if (data.success === false) {
            error.value = data.message || 'Erreur lors de l\'upload';
            if (data.errors) {
                error.value += '\n' + Object.values(data.errors).flat().join('\n');
            }
            return;
        }

        success.value = data.message;
        if (data.count) {
            success.value += ` (${data.count} interventions)`;
        }

        emit('upload-complete');
    } catch (e) {
        console.error('Erreur lors de l\'upload:', e);
        error.value = e.message || 'Erreur lors de l\'upload';
    } finally {
        uploading.value = false;
    }
};

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        uploadFile(file);
    }
};

const handleDrop = (event) => {
    dragover.value = false;
    const file = event.dataTransfer.files[0];
    if (file) {
        uploadFile(file);
    }
};
</script>
