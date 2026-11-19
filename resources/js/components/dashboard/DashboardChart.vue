<script setup>
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Tooltip,
    Legend,
);

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        required: true,
        validator: (value) => value === 'bar' || value === 'line',
    },
    labels: {
        type: Array,
        required: true,
    },
    values: {
        type: Array,
        required: true,
    },
    valueKind: {
        type: String,
        required: true,
        validator: (value) => value === 'count' || value === 'money',
    },
    wholeRubles: {
        type: Boolean,
        default: false,
    },
});

const rubleFormat = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const wholeRubleFormat = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
});

function formatRubles(value) {
    const formatter = props.wholeRubles ? wholeRubleFormat : rubleFormat;

    return formatter.format(value);
}

const chartComponent = computed(() => (props.type === 'bar' ? Bar : Line));

const chartData = computed(() => ({
    labels: props.labels.map(formatAxisLabel),
    datasets: [
        {
            label: props.title,
            data: props.values.map((value) => Number(value)),
            backgroundColor: 'rgba(56, 142, 60, 0.65)',
            borderColor: '#388E3C',
            borderWidth: 2,
            pointRadius: props.type === 'line' ? 2 : 0,
            tension: 0.2,
        },
    ],
}));

const chartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label(context) {
                    const value = context.parsed.y;

                    if (props.valueKind === 'money') {
                        return formatRubles(value);
                    }

                    return String(value);
                },
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: props.valueKind === 'money'
                ? {
                    callback(value) {
                        return formatRubles(value);
                    },
                }
                : {
                    precision: 0,
                },
        },
    },
}));

function formatAxisLabel(label) {
    const text = String(label);
    const day = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);

    if (day) {
        return `${day[3]}.${day[2]}`;
    }

    const month = text.match(/^(\d{4})-(\d{2})$/);

    if (month) {
        const formatted = new Intl.DateTimeFormat('ru-RU', { month: 'short' }).format(
            new Date(Number(month[1]), Number(month[2]) - 1, 1),
        );

        return `${formatted} ${month[1]}`;
    }

    return text;
}
</script>

<template>
    <v-card class="h-100">
        <v-card-title>{{ title }}</v-card-title>
        <v-card-text>
            <div class="dashboard-chart">
                <component :is="chartComponent" :data="chartData" :options="chartOptions" />
            </div>
        </v-card-text>
    </v-card>
</template>

<style scoped>
.dashboard-chart {
    position: relative;
    height: 280px;
}
</style>
