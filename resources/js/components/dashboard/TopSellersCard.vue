<script setup>
defineProps({
    sellers: {
        type: Array,
        required: true,
    },
});

const rubles = new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
});

function formatSum(value) {
    return rubles.format(Number(value));
}
</script>

<template>
    <v-card>
        <v-card-title>Топ продавцов</v-card-title>
        <v-card-text>
            <p v-if="sellers.length === 0" class="mb-0">Нет продаж за текущий месяц</p>
            <v-table v-else>
                <thead>
                    <tr>
                        <th>Место</th>
                        <th>Имя</th>
                        <th>Почта</th>
                        <th>Сумма</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(seller, index) in sellers" :key="seller.user_id">
                        <td>{{ index + 1 }}</td>
                        <td>{{ seller.name || '—' }}</td>
                        <td>{{ seller.email || '—' }}</td>
                        <td>{{ formatSum(seller.sales_sum) }}</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card-text>
    </v-card>
</template>
