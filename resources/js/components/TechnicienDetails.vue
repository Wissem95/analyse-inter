<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="relative flex flex-col w-full h-[90vh] p-6 mx-4 bg-white rounded-lg shadow-xl max-w-8xl">
            <!-- En-tête -->
            <div class="flex justify-between mb-6">
                <h2 class="text-2xl font-bold">Détails - {{ technicien }}</h2>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <span class="sr-only">Fermer</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Contenu principal avec défilement -->
            <div class="flex-1 overflow-y-auto">
                <div v-if="loading" class="flex items-center justify-center py-8">
                    <div class="w-8 h-8 border-4 border-blue-600 rounded-full animate-spin border-t-transparent"></div>
                </div>

                <div v-else-if="error" class="p-4 text-red-600 bg-red-100 rounded">
                    {{ error }}
                </div>

                <div v-else class="space-y-6">
                    <!-- Statistiques globales -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg bg-gray-50">
                            <h3 class="mb-2 text-lg font-medium">Total Interventions</h3>
                            <p class="text-2xl font-bold text-blue-600">{{ stats.global.total_interventions }}</p>
                        </div>
                        <div class="p-4 rounded-lg bg-gray-50">
                            <h3 class="mb-2 text-lg font-medium">Total Revenus</h3>
                            <div class="flex flex-col">
                                <p class="text-2xl font-bold text-blue-600">{{ formatPrice(calculateGlobalTotal()) }}</p>
                                <p class="text-lg text-gray-600">Perçu: {{ formatPrice(calculateGlobalPercus()) }}</p>
                                <p class="text-lg font-semibold" :class="{'text-red-600': calculateGlobalTotal() - calculateGlobalPercus() > 0, 'text-green-600': calculateGlobalTotal() - calculateGlobalPercus() <= 0}">
                                    Solde: {{ formatPrice(calculateGlobalTotal() - calculateGlobalPercus()) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques par type de service -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="mb-4 text-lg font-medium">Par type d'intervention</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="py-2 text-left">Type</th>
                                        <th class="py-2 text-right">Interventions</th>
                                        <th class="py-2 text-right">Revenus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="service in stats.par_service" :key="service.type_service">
                                        <td class="py-2">{{ service.type_service }}</td>
                                        <td class="text-right">{{ service.interventions }}</td>
                                        <td class="text-right">{{ formatPrice(service.revenus) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Évolution mensuelle détaillée -->
                    <div class="p-4 bg-white rounded-lg shadow">
                        <h3 class="mb-4 text-lg font-medium">Détail mensuel des interventions</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr>
                                        <th class="py-2 text-left">Mois</th>
                                        <th class="py-2 text-right">Total</th>
                                        <th class="py-2 text-right">SAV</th>
                                        <th class="py-2 text-right">Reco</th>
                                        <th class="py-2 text-right">Racc Immeuble</th>
                                        <th class="py-2 text-right">Racc Pavillon</th>
                                        <th class="py-2 text-right">Prest Immeuble</th>
                                        <th class="py-2 text-right">Prest Pavillon</th>
                                        <th class="py-2 text-right">Revenus Total</th>
                                        <th class="py-2 text-right">Revenus Perçus</th>
                                        <th class="py-2 text-right">Solde</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="mois in stats.evolution" :key="mois.mois">
                                        <td class="py-2">{{ formatMonth(mois.mois) }}</td>
                                        <td class="text-right">{{ mois.total }}</td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>{{ mois.sav }}</span>
                                                <span class="text-gray-600">({{ formatPrice(mois.revenus_sav) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>{{ mois.reconnexions }}</span>
                                                <span class="text-gray-600">({{ formatPrice(mois.revenus_reconnexions) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>{{ mois.raccordements_immeuble }}</span>
                                                <span class="text-gray-600">({{ formatPrice(mois.revenus_raccordements_immeuble) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>{{ mois.raccordements_pavillon }}</span>
                                                <span class="text-gray-600">({{ formatPrice(mois.revenus_raccordements_pavillon) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <input
                                                    type="number"
                                                    v-model.number="mois.presta_immeuble"
                                                    class="w-24 px-2 py-1 text-right border rounded"
                                                    @input="watchForChanges"
                                                    min="0"
                                                    step="1"
                                                >
                                                <span class="text-gray-600">({{ formatPrice(mois.presta_immeuble * 25) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <input
                                                    type="number"
                                                    v-model.number="mois.presta_pavillon"
                                                    class="w-24 px-2 py-1 text-right border rounded"
                                                    @input="watchForChanges"
                                                    min="0"
                                                    step="1"
                                                >
                                                <span class="text-gray-600">({{ formatPrice(mois.presta_pavillon * 45) }})</span>
                                            </div>
                                        </td>
                                        <td class="text-right">{{ formatPrice(calculateTotalRevenue(mois)) }}</td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end space-x-2">
                                                <input
                                                    type="number"
                                                    v-model.number="mois.revenus_percus"
                                                    class="w-24 px-2 py-1 text-right border rounded"
                                                    @input="watchForChanges"
                                                >
                                            </div>
                                        </td>
                                        <td class="text-right" :class="{'text-red-600': calculateTotalRevenue(mois) - (mois.revenus_percus || 0) > 0, 'text-green-600': calculateTotalRevenue(mois) - (mois.revenus_percus || 0) <= 0}">
                                            {{ formatPrice(calculateTotalRevenue(mois) - (mois.revenus_percus || 0)) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton de sauvegarde fixe en bas -->
            <div class="sticky bottom-0 flex justify-end pt-4 mt-4 bg-white border-t">
                <button
                    @click="saveAllChanges"
                    class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                    :disabled="saving"
                >
                    <div class="flex items-center space-x-2">
                        <span v-if="!saving">Sauvegarder</span>
                        <span v-else>Sauvegarde en cours...</span>
                        <div v-if="saving" class="w-4 h-4 border-2 border-white rounded-full animate-spin border-t-transparent"></div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    technicien: {
        type: String,
        required: true
    },
    dateFilter: {
        type: Object,
        required: true
    }
});

const stats = ref({
    global: { total_interventions: 0, total_revenus: 0 },
    par_service: [],
    evolution: []
});
const loading = ref(true);
const error = ref(null);

const saving = ref(false);
const unsavedChanges = ref(false);

const formatPrice = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(value);
};

const formatMonth = (monthStr) => {
    const date = new Date(monthStr + '-01');
    return new Intl.DateTimeFormat('fr-FR', {
        year: 'numeric',
        month: 'long'
    }).format(date);
};

const loadStats = async () => {
    loading.value = true;
    error.value = null;

    try {
        const params = new URLSearchParams();
        if (props.dateFilter.debut) params.append('debut', props.dateFilter.debut);
        if (props.dateFilter.fin) params.append('fin', props.dateFilter.fin);

        const response = await fetch(`/api/stats/technicien/${encodeURIComponent(props.technicien)}?${params.toString()}`);
        if (!response.ok) throw new Error('Erreur lors du chargement des statistiques');

        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Erreur lors du chargement des statistiques');

        // Initialiser les valeurs par défaut pour les prestations
        data.evolution = data.evolution.map(mois => ({
            ...mois,
            revenus_presta_immeuble: mois.revenus_presta_immeuble || 25,
            revenus_presta_pavillon: mois.revenus_presta_pavillon || 45,
            revenus_total: 0 // Sera calculé ci-dessous
        }));

        // Calculer les totaux initiaux
        data.evolution = data.evolution.map(mois => ({
            ...mois,
            revenus_total: calculateTotalRevenue(mois)
        }));

        stats.value = data;
    } catch (e) {
        error.value = e.message;
    } finally {
        loading.value = false;
    }
};

const calculatePrestaRevenue = (mois) => {
    // Prix fixes pour les prestations
    const PRIX_IMMEUBLE = 25;
    const PRIX_PAVILLON = 45;

    // Calculer les revenus en multipliant le nombre par le prix fixe
    const immeuble = Number(mois.presta_immeuble || 0) * PRIX_IMMEUBLE;
    const pavillon = Number(mois.presta_pavillon || 0) * PRIX_PAVILLON;

    return immeuble + pavillon;
};

const calculateTotalRevenue = (mois) => {
    // S'assurer que toutes les valeurs sont des nombres
    const sav = Number(mois.revenus_sav || 0);
    const reconnexions = Number(mois.revenus_reconnexions || 0);
    const raccordements_immeuble = Number(mois.revenus_raccordements_immeuble || 0);
    const raccordements_pavillon = Number(mois.revenus_raccordements_pavillon || 0);
    const prestations = calculatePrestaRevenue(mois);

    // Calculer le total
    return sav + reconnexions + raccordements_immeuble + raccordements_pavillon + prestations;
};

const getHeaders = () => {
    const headers = {
        'Content-Type': 'application/json'
    };

    // Essayer de récupérer le token CSRF s'il existe
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    return headers;
};

const calculateGlobalTotal = () => {
    return stats.value.evolution.reduce((total, mois) => {
        return total + calculateTotalRevenue(mois);
    }, 0);
};

const calculateGlobalPercus = () => {
    return stats.value.evolution.reduce((total, mois) => {
        return total + Number(mois.revenus_percus || 0);
    }, 0);
};

const saveAllChanges = async () => {
    saving.value = true;
    error.value = null;
    try {
        // Sauvegarder les revenus perçus pour chaque mois
        for (const mois of stats.value.evolution) {
            // Mettre à jour les revenus perçus
            const revenuResponse = await fetch(`/api/interventions/update-revenu-percu`, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    mois: mois.mois,
                    technicien: props.technicien,
                    revenus_percus: parseFloat(mois.revenus_percus || 0)
                })
            });

            if (!revenuResponse.ok) {
                throw new Error('Erreur lors de la mise à jour des revenus perçus');
            }

            // Mettre à jour les prestations immeuble
            const immeubleResponse = await fetch(`/api/interventions/update-presta-revenue`, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    mois: mois.mois,
                    technicien: props.technicien,
                    type_habitation: 'immeuble',
                    revenus: 25, // Prix fixe pour immeuble
                    nombre: mois.presta_immeuble || 0 // Ajout du nombre de prestations
                })
            });

            if (!immeubleResponse.ok) {
                throw new Error('Erreur lors de la mise à jour des prestations immeuble');
            }

            // Mettre à jour les prestations pavillon
            const pavillonResponse = await fetch(`/api/interventions/update-presta-revenue`, {
                method: 'POST',
                headers: getHeaders(),
                body: JSON.stringify({
                    mois: mois.mois,
                    technicien: props.technicien,
                    type_habitation: 'pavillon',
                    revenus: 45, // Prix fixe pour pavillon
                    nombre: mois.presta_pavillon || 0 // Ajout du nombre de prestations
                })
            });

            if (!pavillonResponse.ok) {
                throw new Error('Erreur lors de la mise à jour des prestations pavillon');
            }
        }

        // Mettre à jour les totaux globaux
        stats.value.global.total_revenus = calculateGlobalTotal();
        stats.value.global.total_revenus_percus = calculateGlobalPercus();

        // Marquer comme sauvegardé
        unsavedChanges.value = false;
    } catch (e) {
        console.error('Erreur lors de la sauvegarde:', e);
        error.value = e.message || 'Erreur lors de la sauvegarde des modifications';
    } finally {
        saving.value = false;
    }
};

const watchForChanges = () => {
    unsavedChanges.value = true;

    // Mettre à jour les calculs pour chaque mois
    stats.value.evolution = stats.value.evolution.map(mois => {
        const newTotal = calculateTotalRevenue(mois);
        return {
            ...mois,
            revenus_total: newTotal
        };
    });

    // Forcer la réactivité
    stats.value = { ...stats.value };
};

onMounted(loadStats);
</script>

<style scoped>
.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
