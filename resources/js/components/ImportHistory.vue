<template>
    <div class="p-6 bg-white rounded-lg shadow">
        <h2 class="mb-4 text-lg font-medium">Historique des imports</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="py-2 text-left">Fichier</th>
                        <th class="py-2 text-right">Nombre d'enregistrements</th>
                        <th class="py-2 text-center">Statut</th>
                        <th class="py-2 text-center">Date d'import</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="importItem in imports" :key="importItem.id">
                        <td class="py-2">{{ importItem.filename }}</td>
                        <td class="text-right">{{ importItem.records_count }}</td>
                        <td class="text-center">
                            <span :class="{
                                'px-2 py-1 text-sm rounded-full': true,
                                'bg-green-100 text-green-800': importItem.status === 'success',
                                'bg-yellow-100 text-yellow-800': importItem.status === 'partial',
                                'bg-red-100 text-red-800': importItem.status === 'failed'
                            }">
                                {{ formatStatus(importItem.status) }}
                            </span>
                        </td>
                        <td class="text-center">{{ formatDate(importItem.import_date) }}</td>
                        <td class="text-right">
                            <button
                                @click="deleteImport(importItem)"
                                class="px-3 py-1 text-sm text-red-600 border border-red-600 rounded hover:bg-red-50"
                                :disabled="deleting === importItem.id"
                            >
                                <span v-if="deleting === importItem.id">Suppression...</span>
                                <span v-else>Supprimer</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const imports = ref([]);
const deleting = ref(null);

const formatStatus = (status) => {
    switch (status) {
        case 'success': return 'Succès';
        case 'partial': return 'Partiel';
        case 'failed': return 'Échec';
        default: return status;
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('fr-FR');
};

const loadHistory = async () => {
    try {
        const response = await fetch('/api/import/history');
        const data = await response.json();
        if (data.success) {
            imports.value = data.history;
        }
    } catch (error) {
        console.error('Erreur lors du chargement de l\'historique:', error);
    }
};

const deleteImport = async (importItem) => {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer l'import "${importItem.filename}" ?`)) {
        return;
    }

    deleting.value = importItem.id;
    try {
        const response = await fetch(`/api/import/${importItem.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        const data = await response.json();
        if (data.success) {
            await loadHistory();
        } else {
            alert(data.message || 'Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        alert('Erreur lors de la suppression');
    } finally {
        deleting.value = null;
    }
};

onMounted(loadHistory);
</script>
