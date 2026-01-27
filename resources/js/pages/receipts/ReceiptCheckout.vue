<script setup>
import ReceiptDetails from "@/components/receipts/ReceiptDetails.vue";
import { reactive, ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";

const currentRoute = useRoute();
const router = useRouter();

const receipt = reactive({});
const loading = ref(true);
const processing = ref(false);

function loadReceipt() {
    loading.value = true;
    axios
        .get(
            route("receipts.checkout-data", {
                receipt: currentRoute.params.id,
            }),
        )
        .then((response) => {
            Object.assign(receipt, response.data);
        })
        .catch((error) => {
            if (error.response?.status === 404) {
                router.push({ name: "404" });
            } else {
                console.error("Ошибка загрузки чека:", error);
                router.push({ name: "404" });
            }
        })
        .finally(() => {
            loading.value = false;
        });
}

function initiatePayment() {
    if (processing.value) {
        return;
    }

    processing.value = true;

    axios
        .post(route("receipts.checkout", { receipt: currentRoute.params.id }))
        .then((response) => {
            if (response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            }
        })
        .catch((error) => {
            console.error("Ошибка инициации платежа:", error);
            alert(
                error.response?.data?.message ||
                    "Произошла ошибка при инициации платежа",
            );
        })
        .finally(() => {
            processing.value = false;
        });
}

onMounted(() => {
    loadReceipt();
});
</script>

<template>
    <div
        class="justify-center d-flex align-center"
        style="min-height: 100vh; padding: 20px"
    >
        <v-card class="pa-6" max-width="800" width="100%">
            <v-card-title class="mb-4 text-h5">
                Оплата договора страхования
            </v-card-title>

            <v-card-text v-if="loading">
                <v-progress-circular
                    indeterminate
                    color="primary"
                ></v-progress-circular>
                <span class="ml-4">Загрузка данных чека...</span>
            </v-card-text>

            <v-card-text v-else>
                <div class="justify-center mb-6 d-flex">
                    <ReceiptDetails :receipt="receipt" width="100%" />
                </div>

                <div class="justify-center d-flex">
                    <v-btn
                        color="primary"
                        size="large"
                        :loading="processing"
                        :disabled="processing"
                        @click="initiatePayment"
                        prepend-icon="mdi-credit-card"
                    >
                        Оплатить онлайн
                    </v-btn>
                </div>
            </v-card-text>
        </v-card>
    </div>
</template>
