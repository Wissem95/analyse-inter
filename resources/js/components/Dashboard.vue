<template>
    <div class="p-6 space-y-6">
        <!-- Sélecteur de période -->
        <div class="p-4 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-medium">Sélectionner une période</h2>
            <div class="flex flex-wrap gap-4">
                <button
                    v-for="periode in stats.periodes"
                    :key="periode.semestre"
                    class="px-4 py-2 rounded-lg"
                    :class="{
                        'bg-blue-600 text-white': isActivePeriode(periode),
                        'bg-gray-100 hover:bg-gray-200': !isActivePeriode(periode)
                    }"
                    @click="selectPeriode(periode)"
                >
                    {{ periode.semestre }}
                </button>
                <button
                    class="px-4 py-2 rounded-lg"
                    :class="{
                        'bg-blue-600 text-white': dateFilter.debut === '',
                        'bg-gray-100 hover:bg-gray-200': dateFilter.debut !== ''
                    }"
                    @click="resetDateFilter"
                >
                    Tout
                </button>
            </div>
        </div>

        <!-- Filtres de date personnalisés -->
        <DateFilter
            :value="dateFilter"
            @filter="applyDateFilter"
        />

        <!-- Statistiques globales -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-2 text-lg font-medium text-gray-900">Total Interventions</h3>
                <p class="text-3xl font-bold text-blue-600">{{ stats.global?.total_interventions || 0 }}</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-2 text-lg font-medium text-gray-900">Total Revenus Perçus</h3>
                <p class="text-3xl font-bold text-blue-600">{{ formatPrice(stats.global?.total_revenus_percus || 0) }}</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow">
                <h3 class="mb-2 text-lg font-medium text-gray-900">Total Revenus</h3>
                <p class="text-3xl font-bold text-blue-600">{{ formatPrice(stats.global?.total_revenus || 0) }}</p>
            </div>
        </div>

        <!-- Graphique d'évolution mensuelle -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-medium">Évolution mensuelle</h2>
            <div class="h-80">
                <Line
                    :data="evolutionData"
                    :options="chartOptions"
                />
            </div>
        </div>

        <!-- Statistiques par technicien -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-medium">Par technicien</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="py-2 text-left">Technicien</th>
                            <th class="py-2 text-right">Interventions</th>
                            <th class="py-2 text-right">Revenus</th>
                            <th class="py-2 text-right">Revenus Perçus</th>
                            <th class="py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tech in stats.par_technicien" :key="tech.technicien">
                            <td class="py-2">{{ tech.technicien }}</td>
                            <td class="text-right">{{ tech.interventions }}</td>
                            <td class="text-right">{{ formatPrice(tech.revenus) }}</td>
                            <td class="text-right">{{ formatPrice(tech.revenus_percus) }}</td>
                            <td class="text-right">
                                <button
                                    @click="showTechnicienDetails(tech)"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    Détails
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal pour les détails du technicien -->
        <TechnicienDetails
            v-if="selectedTechnicien"
            :technicien="selectedTechnicien.technicien"
            :date-filter="dateFilter"
            @close="selectedTechnicien = null"
        />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import { Line } from 'vue-chartjs';
import DateFilter from './DateFilter.vue';
import TechnicienDetails from './TechnicienDetails.vue';

// Enregistrer les composants Chart.js nécessaires
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
);

const stats = ref({
    global: null,
    par_technicien: [],
    par_service: [],
    par_mois: [],
    periodes: []
});

const dateFilter = ref({
    debut: '',
    fin: ''
});

const selectedTechnicien = ref(null);

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            beginAtZero: true
        }
    }
};

const evolutionData = computed(() => ({
    labels: stats.value.par_mois.map(m => m.mois),
    datasets: [
        {
            label: 'Revenus',
            data: stats.value.par_mois.map(m => m.revenus),
            borderColor: 'rgb(59, 130, 246)',
            tension: 0.1
        },
        {
            label: 'SAV',
            data: stats.value.par_mois.map(m => m.details.sav),
            borderColor: 'rgb(239, 68, 68)',
            tension: 0.1
        },
        {
            label: 'Raccordements',
            data: stats.value.par_mois.map(m => m.details.raccordements),
            borderColor: 'rgb(34, 197, 94)',
            tension: 0.1
        },
        {
            label: 'Reconnexions',
            data: stats.value.par_mois.map(m => m.details.reconnexions),
            borderColor: 'rgb(234, 179, 8)',
            tension: 0.1
        }
    ]
}));

const formatNumber = (value) => {
    return new Intl.NumberFormat('fr-FR').format(value);
};

const formatPrice = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(value);
};

const loadStats = async () => {
    try {
        const params = new URLSearchParams();
        if (dateFilter.value.debut) params.append('debut', dateFilter.value.debut);
        if (dateFilter.value.fin) params.append('fin', dateFilter.value.fin);

        const response = await fetch(`/api/stats?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Content-Type:', contentType);
            throw new Error('La réponse n\'est pas au format JSON');
        }

        const rawText = await response.text();
        console.log('Raw response:', rawText);

        let data;
        try {
            data = JSON.parse(rawText);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('La réponse n\'est pas au format JSON valide');
        }

        if (!data || typeof data !== 'object') {
            throw new Error('La réponse ne contient pas de données valides');
        }

        if (data.success === false) {
            throw new Error(data.message || 'Erreur lors du chargement des statistiques');
        }

        // Vérifier que les données sont bien structurées
        if (!data.global || !data.par_technicien || !data.par_service || !data.evolution) {
            console.error('Invalid data structure:', data);
            throw new Error('Les données reçues ne sont pas dans le format attendu');
        }

        // Convertir evolution en par_mois pour la compatibilité
        data.par_mois = data.evolution;
        delete data.evolution;

        stats.value = data;
    } catch (error) {
        console.error('Erreur lors du chargement des statistiques:', error);
        // Réinitialiser les stats en cas d'erreur
        stats.value = {
            global: {
                total_interventions: 0,
                total_revenus: 0,
                total_heures: 0
            },
            par_technicien: [],
            par_service: [],
            par_mois: [],
            periodes: []
        };
        throw error; // Propager l'erreur pour que le composant parent puisse la gérer
    }
};

const applyDateFilter = (filter) => {
    dateFilter.value = filter;
    loadStats();
};

const selectPeriode = (periode) => {
    dateFilter.value = {
        debut: periode.debut,
        fin: periode.fin
    };
    loadStats();
};

const resetDateFilter = () => {
    dateFilter.value = {
        debut: '',
        fin: ''
    };
    loadStats();
};

const isActivePeriode = (periode) => {
    return dateFilter.value.debut === periode.debut &&
           dateFilter.value.fin === periode.fin;
};

const showTechnicienDetails = (tech) => {
    selectedTechnicien.value = tech;
};

onMounted(loadStats);

// Exposer loadStats pour qu'elle soit accessible depuis l'extérieur
defineExpose({ loadStats });
</script>
