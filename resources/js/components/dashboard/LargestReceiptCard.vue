<script setup>
import { computed } from 'vue';

const props = defineProps({
    receipt: {
        type: Object,
        default: null,
    },
});

const rubles = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
});

const amount = computed(() => {
    if (!props.receipt) {
        return '';
    }

    return rubles.format(Number(props.receipt.amount));
});

const submittedAt = computed(() => {
    if (!props.receipt?.submited_at) {
        return '';
    }

    return new Date(props.receipt.submited_at).toLocaleString('ru-RU');
});

const cashier = computed(() => {
    if (!props.receipt) {
        return '';
    }

    const name = props.receipt.user_name || '—';
    const email = props.receipt.user_email || '—';

    return `${name}, ${email}`;
});

const contract = computed(() => {
    if (!props.receipt) {
        return '';
    }

    const series = props.receipt.contract_series || '—';
    const number = props.receipt.contract_number || '—';

    return `${series} ${number}`;
});
</script>

<template>
    <v-card>
        <v-card-title>Самый крупный чек</v-card-title>
        <v-card-text>
            <p v-if="!receipt" class="mb-0">Нет пробитых чеков за текущий месяц</p>
            <div v-else class="d-flex flex-column ga-2">
                <div class="text-h5">{{ amount }}</div>
                <div>Дата пробития: {{ submittedAt }}</div>
                <div>Кассир: {{ cashier }}</div>
                <div>Агентство: {{ receipt.agency_name || '—' }}</div>
                <div>Договор: {{ contract }}</div>
            </div>
        </v-card-text>
    </v-card>
</template>
