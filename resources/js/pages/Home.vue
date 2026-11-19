<script setup>
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import CrudPage from '@/components/CrudPage.vue';
import H1 from '@/components/H1.vue';
import DashboardChart from '@/components/dashboard/DashboardChart.vue';
import LargestReceiptCard from '@/components/dashboard/LargestReceiptCard.vue';
import TopSellersCard from '@/components/dashboard/TopSellersCard.vue';
import { useUserStore } from '@/stores/user';
import { useDashboardStats } from '@/composables/useDashboardStats';

const userStore = useUserStore();

const dashboardVisible = computed(() => userStore.user?.dashboard_visible === true);

const agencyId = computed(() => {
    if (!dashboardVisible.value) {
        return null;
    }

    return userStore.activeAgency?.id ?? null;
});

const { stats, loading } = useDashboardStats(agencyId);

const days = computed(() => stats.value?.days ?? []);
const months = computed(() => stats.value?.months ?? []);
const dayLabels = computed(() => days.value.map((day) => day.date));
const receiptCounts = computed(() => days.value.map((day) => day.receipts_count));
const dailySales = computed(() => days.value.map((day) => day.sales_sum));
const dailyRefunds = computed(() => days.value.map((day) => day.refunds_sum));
const monthLabels = computed(() => months.value.map((month) => month.month));
const monthlySales = computed(() => months.value.map((month) => month.sales_sum));
</script>

<template>
    <AppLayout>
        <CrudPage>
            <template v-slot:header>
                <H1>Dashboard</H1>
            </template>
            <template v-slot:content>
                <template v-if="dashboardVisible && agencyId">
                    <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />
                    <template v-else-if="stats">
                        <v-row>
                            <v-col cols="12" md="6">
                                <DashboardChart
                                    title="Количество чеков"
                                    type="bar"
                                    value-kind="count"
                                    :labels="dayLabels"
                                    :values="receiptCounts"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <DashboardChart
                                    title="Продажи по дням"
                                    type="line"
                                    value-kind="money"
                                    whole-rubles
                                    :labels="dayLabels"
                                    :values="dailySales"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <DashboardChart
                                    title="Возвраты по дням"
                                    type="line"
                                    value-kind="money"
                                    :labels="dayLabels"
                                    :values="dailyRefunds"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <DashboardChart
                                    title="Продажи по месяцам"
                                    type="bar"
                                    value-kind="money"
                                    whole-rubles
                                    :labels="monthLabels"
                                    :values="monthlySales"
                                />
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <LargestReceiptCard :receipt="stats.largest_receipt" />
                            </v-col>
                            <v-col cols="12">
                                <TopSellersCard :sellers="stats.top_sellers" />
                            </v-col>
                        </v-row>
                    </template>
                </template>
            </template>
        </CrudPage>
    </AppLayout>
</template>
