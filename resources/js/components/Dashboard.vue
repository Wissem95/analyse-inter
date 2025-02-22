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

        <!-- Historique des imports -->
        <ImportHistory />

        <!-- Graphique d'évolution mensuelle -->
        <div class="p-6 bg-white rounded-lg shadow">
            <h2 class="mb-4 text-lg font-medium">Évolution mensuelle</h2>
            <div class="relative w-full">
                <Carousel
                    v-model="currentSlide"
                    :items-to-show="1"
                    :wrap-around="true"
                    :transition="500"
                    class="h-[400px]"
                    ref="carousel"
                    :autoplay="3000"
                    pause-autoplay-on-hover
                >
                    <!-- Graphique des revenus -->
                    <Slide v-slot="{ isActive }" :index="0">
                        <div class="w-full h-full px-2">
                            <h3 class="mb-2 text-lg font-medium text-center">Revenus Totaux</h3>
                            <div class="h-[350px]">
                                <Line
                                    v-show="isActive"
                                    :data="revenusChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </Slide>

                    <!-- Graphique des SAV -->
                    <Slide v-slot="{ isActive }" :index="1">
                        <div class="w-full h-full px-2">
                            <h3 class="mb-2 text-lg font-medium text-center">SAV</h3>
                            <div class="h-[350px]">
                                <Line
                                    v-show="isActive"
                                    :data="savChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </Slide>

                    <!-- Graphique des Raccordements -->
                    <Slide v-slot="{ isActive }" :index="2">
                        <div class="w-full h-full px-2">
                            <h3 class="mb-2 text-lg font-medium text-center">Raccordements</h3>
                            <div class="h-[350px]">
                                <Line
                                    v-show="isActive"
                                    :data="raccordementsChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </Slide>

                    <!-- Graphique des Reconnexions -->
                    <Slide v-slot="{ isActive }" :index="3">
                        <div class="w-full h-full px-2">
                            <h3 class="mb-2 text-lg font-medium text-center">Reconnexions</h3>
                            <div class="h-[350px]">
                                <Line
                                    v-show="isActive"
                                    :data="reconnexionsChartData"
                                    :options="chartOptions"
                                />
                            </div>
                        </div>
                    </Slide>

                    <template #addons>
                        <Navigation />
                        <Pagination />
                    </template>
                </Carousel>
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
import { Carousel, Navigation, Pagination, Slide } from 'vue3-carousel';
import 'vue3-carousel/dist/carousel.css';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';
import DateFilter from './DateFilter.vue';
import TechnicienDetails from './TechnicienDetails.vue';
import ImportHistory from './ImportHistory.vue';

// Enregistrer les composants Chart.js nécessaires
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
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

// Référence pour le carrousel
const carousel = ref(null);
const currentSlide = ref(0);

// Options communes pour tous les graphiques
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 400
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(0, 0, 0, 0.1)',
            },
            ticks: {
                callback: function(value) {
                    if (this.chart.canvas.id.includes('revenus')) {
                        return new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: 'EUR',
                            maximumFractionDigits: 0
                        }).format(value);
                    }
                    return value;
                }
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    },
    plugins: {
        legend: {
            display: true,
            position: 'top'
        },
        tooltip: {
            mode: 'index',
            intersect: false,
            backgroundColor: 'rgba(255, 255, 255, 0.9)',
            titleColor: '#1f2937',
            bodyColor: '#1f2937',
            borderColor: '#e5e7eb',
            borderWidth: 1,
            padding: 10,
            displayColors: true,
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    let value = context.parsed.y;

                    if (label.toLowerCase().includes('revenus')) {
                        return `${label}: ${new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: 'EUR'
                        }).format(value)}`;
                    }

                    return `${label}: ${new Intl.NumberFormat('fr-FR').format(value)}`;
                }
            }
        }
    }
};

// Données pour chaque graphique
const revenusChartData = computed(() => {
    // Regrouper les revenus par mois
    const revenusParMois = {};
    stats.value.par_mois.forEach(mois => {
        if (!revenusParMois[mois.mois]) {
            revenusParMois[mois.mois] = 0;
        }
        revenusParMois[mois.mois] += mois.revenus || 0;
    });

    return {
        labels: Object.keys(revenusParMois),
        datasets: [{
            label: 'Revenus Totaux',
            data: Object.values(revenusParMois),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.1,
            fill: true
        }]
    };
});

const savChartData = computed(() => {
    // Regrouper les SAV par mois
    const savParMois = {};
    stats.value.par_mois.forEach(mois => {
        if (!savParMois[mois.mois]) {
            savParMois[mois.mois] = 0;
        }
        savParMois[mois.mois] += mois.details?.sav || 0;
    });

    return {
        labels: Object.keys(savParMois),
        datasets: [{
            label: 'SAV Totaux',
            data: Object.values(savParMois),
            borderColor: 'rgb(239, 68, 68)',
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            tension: 0.1,
            fill: true
        }]
    };
});

const raccordementsChartData = computed(() => {
    // Regrouper les raccordements par mois
    const raccordementsParMois = {};
    stats.value.par_mois.forEach(mois => {
        if (!raccordementsParMois[mois.mois]) {
            raccordementsParMois[mois.mois] = 0;
        }
        raccordementsParMois[mois.mois] += mois.details?.raccordements || 0;
    });

    return {
        labels: Object.keys(raccordementsParMois),
        datasets: [{
            label: 'Raccordements Totaux',
            data: Object.values(raccordementsParMois),
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.1,
            fill: true
        }]
    };
});

const reconnexionsChartData = computed(() => {
    // Regrouper les reconnexions par mois
    const reconnexionsParMois = {};
    stats.value.par_mois.forEach(mois => {
        if (!reconnexionsParMois[mois.mois]) {
            reconnexionsParMois[mois.mois] = 0;
        }
        reconnexionsParMois[mois.mois] += mois.details?.reconnexions || 0;
    });

    return {
        labels: Object.keys(reconnexionsParMois),
        datasets: [{
            label: 'Reconnexions Totaux',
            data: Object.values(reconnexionsParMois),
            borderColor: 'rgb(234, 179, 8)',
            backgroundColor: 'rgba(234, 179, 8, 0.1)',
            tension: 0.1,
            fill: true
        }]
    };
});

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
        if (dateFilter.value.debut) {
            params.append('debut', dateFilter.value.debut);
        }
        if (dateFilter.value.fin) {
            params.append('fin', dateFilter.value.fin);
        }

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

const applyDateFilter = async (filter) => {
    dateFilter.value = filter;
    await loadStats();
};

const selectPeriode = async (periode) => {
    dateFilter.value = {
        debut: periode.debut,
        fin: periode.fin
    };
    await loadStats();
};

const resetDateFilter = async () => {
    dateFilter.value = {
        debut: '',
        fin: ''
    };
    await loadStats();
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

<style>
.carousel__slide {
    @apply w-full;
}

.carousel__viewport {
    @apply w-full;
}

.carousel__track {
    @apply w-full;
}

.carousel__pagination {
    @apply mt-4;
}

.carousel__pagination-button {
    @apply w-3 h-3 rounded-full bg-gray-300 mx-1 transition-all duration-300;
}

.carousel__pagination-button--active {
    @apply bg-blue-600;
}

.carousel__prev,
.carousel__next {
    @apply absolute top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center
           bg-white rounded-full shadow-lg z-10 transition-all duration-300
           hover:bg-gray-50 hover:scale-110;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.carousel__prev {
    @apply left-2;
}

.carousel__next {
    @apply right-2;
}

/* Optimisation pour les appareils mobiles */
@media (max-width: 640px) {
    .carousel__prev,
    .carousel__next {
        @apply w-8 h-8;
    }
}
</style>
