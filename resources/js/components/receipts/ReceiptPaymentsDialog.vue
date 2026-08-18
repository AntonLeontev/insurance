<script setup>
import {
    paymentStatusColor,
    paymentStatusIcon,
    paymentStatusLabel,
} from "@/utils/paymentStatus";

const isOpen = defineModel({ type: Boolean, default: false });

const props = defineProps({
    payments: { type: Array, default: () => [] },
});

function formatDate(value) {
    if (!value) {
        return "";
    }

    return new Date(value).toLocaleString("ru-RU");
}
</script>

<template>
    <v-dialog
        v-model="isOpen"
        width="auto"
        max-width="600"
        min-width="400"
        scrollable
    >
        <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
                <span class="d-flex align-center ga-2">
                    <v-icon icon="mdi-cash" />
                    Платежи
                </span>
                <v-btn
                    icon="mdi-close"
                    variant="plain"
                    @click="isOpen = false"
                ></v-btn>
            </v-card-title>

            <v-card-text>
                <v-list v-if="props.payments.length">
                    <v-list-item
                        v-for="payment in props.payments"
                        :key="payment.id"
                        class="align-start"
                    >
                        <template v-slot:prepend>
                            <v-icon
                                :icon="paymentStatusIcon(payment)"
                                :color="paymentStatusColor(payment)"
                            />
                        </template>

                        <v-list-item-title>
                            {{ paymentStatusLabel(payment) }}
                        </v-list-item-title>

                        <div class="text-caption text-medium-emphasis payment-details">
                            <div>
                                Создан: {{ formatDate(payment.created_at) }}
                            </div>
                            <div v-if="payment.paid_at">
                                Оплачен: {{ formatDate(payment.paid_at) }}
                            </div>
                            <div v-if="payment.payment_id">
                                ID платежа: {{ payment.payment_id }}
                            </div>
                        </div>
                    </v-list-item>
                </v-list>
            </v-card-text>

            <v-card-actions>
                <v-btn @click="isOpen = false">Закрыть</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
.payment-details {
    white-space: normal;
}

:deep(.v-list-item__content) {
    overflow: visible;
}
</style>
