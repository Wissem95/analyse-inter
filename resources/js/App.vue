<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white shadow-sm">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">
                            Analyseur d'Interventions
                        </h1>
                    </div>
                </div>
            </div>
        </nav>

        <main class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <FileUpload @upload-complete="refreshDashboard" />

                <div class="mt-6">
                    <Dashboard ref="dashboard" />
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import FileUpload from './components/FileUpload.vue';
import Dashboard from './components/Dashboard.vue';

const dashboard = ref(null);

const refreshDashboard = async () => {
    // Attendre un court instant pour que les données soient bien enregistrées
    await new Promise(resolve => setTimeout(resolve, 500));

    try {
        if (dashboard.value && typeof dashboard.value.loadStats === 'function') {
            await dashboard.value.loadStats();
        } else {
            console.error('Le composant Dashboard n\'est pas correctement initialisé');
        }
    } catch (error) {
        console.error('Erreur lors du rafraîchissement du dashboard:', error);
        // Ici vous pourriez ajouter un toast ou une notification pour l'utilisateur
    }
};

onMounted(() => {
    refreshDashboard();
});
</script>
