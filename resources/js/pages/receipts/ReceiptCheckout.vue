<script setup>
import ReceiptDetails from "@/components/receipts/ReceiptDetails.vue";
import { computed, reactive, ref, onMounted, onUnmounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useDisplay } from "vuetify";
import axios from "axios";

const currentRoute = useRoute();
const router = useRouter();
const { mobile } = useDisplay();

const receipt = reactive({});
const loading = ref(true);
const processing = ref(false);
const sbpDialog = ref(false);
const sbpQrLoading = ref(false);
const sbpBanksLoading = ref(false);
const sbpError = ref("");
const qrSvg = ref("");
const banks = ref([]);
const bankQuery = ref("");
const selectedBankId = ref(null);
const deeplinkLoading = ref(false);

let statusTimer = null;

const sbpLoading = computed(() =>
    mobile.value
        ? sbpQrLoading.value || sbpBanksLoading.value
        : sbpQrLoading.value,
);

const qrDataUri = computed(() => {
    if (!qrSvg.value) {
        return "";
    }

    return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(qrSvg.value)}`;
});

const filteredBanks = computed(() => {
    const query = bankQuery.value.trim().toLowerCase();
    const sorted = [...banks.value].sort(
        (left, right) => (left.BankOrder ?? 0) - (right.BankOrder ?? 0),
    );

    if (!query) {
        return sorted;
    }

    return sorted.filter((bank) =>
        String(bank.BankName ?? "")
            .toLowerCase()
            .includes(query),
    );
});

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
            if (response.data.sbp) {
                openSbpDialog();
                return;
            }

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

function openSbpDialog() {
    sbpError.value = "";
    qrSvg.value = "";
    banks.value = [];
    bankQuery.value = "";
    selectedBankId.value = null;
    sbpDialog.value = true;
    startStatusPolling();
    loadQr();

    if (mobile.value) {
        loadBanks();
    }
}

function loadQr() {
    sbpQrLoading.value = true;
    sbpError.value = "";

    axios
        .post(route("receipts.sbp-qr", { receipt: currentRoute.params.id }))
        .then((response) => {
            qrSvg.value = response.data.qr_svg ?? "";
        })
        .catch((error) => {
            console.error("Ошибка получения QR СБП:", error);
            sbpError.value =
                error.response?.data?.message ||
                "Не удалось получить QR-код для оплаты";
        })
        .finally(() => {
            sbpQrLoading.value = false;
        });
}

function loadBanks() {
    sbpBanksLoading.value = true;

    axios
        .get(
            route("receipts.sbp-banks", {
                receipt: currentRoute.params.id,
                device: "mobile",
            }),
        )
        .then((response) => {
            banks.value = response.data.banks ?? [];
        })
        .catch((error) => {
            console.error("Ошибка загрузки списка банков СБП:", error);
            sbpError.value =
                error.response?.data?.message ||
                "Не удалось загрузить список банков";
        })
        .finally(() => {
            sbpBanksLoading.value = false;
        });
}

function payInBankApp() {
    if (!selectedBankId.value || deeplinkLoading.value) {
        return;
    }

    deeplinkLoading.value = true;

    axios
        .post(route("receipts.sbp-deeplink", { receipt: currentRoute.params.id }), {
            bank_id: selectedBankId.value,
        })
        .then((response) => {
            if (response.data.deeplink) {
                window.location.href = response.data.deeplink;
            }
        })
        .catch((error) => {
            console.error("Ошибка получения ссылки СБП:", error);
            sbpError.value =
                error.response?.data?.message ||
                "Не удалось открыть приложение банка";
        })
        .finally(() => {
            deeplinkLoading.value = false;
        });
}

function startStatusPolling() {
    stopStatusPolling();

    statusTimer = setInterval(() => {
        axios
            .get(
                route("receipts.payment-status", {
                    receipt: currentRoute.params.id,
                }),
            )
            .then((response) => {
                if (
                    response.data.status === "CONFIRMED" ||
                    response.data.is_draft === false
                ) {
                    stopStatusPolling();
                    sbpDialog.value = false;
                    router.push({
                        name: "receipts.payment-success",
                        params: { id: currentRoute.params.id },
                    });
                }
            })
            .catch(() => {});
    }, 3000);
}

function stopStatusPolling() {
    if (!statusTimer) {
        return;
    }

    clearInterval(statusTimer);
    statusTimer = null;
}

function closeSbpDialog() {
    sbpDialog.value = false;
}

watch(sbpDialog, (isOpen) => {
    if (!isOpen) {
        stopStatusPolling();
    }
});

onMounted(() => {
    loadReceipt();
});

onUnmounted(() => {
    stopStatusPolling();
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

        <v-dialog v-model="sbpDialog" max-width="480">
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    Оплата через СБП
                    <v-btn
                        icon="mdi-close"
                        variant="plain"
                        @click="closeSbpDialog"
                    />
                </v-card-title>
                <v-card-text>
                    <div v-if="sbpLoading" class="justify-center py-6 d-flex">
                        <v-progress-circular indeterminate color="primary" />
                    </div>
                    <v-alert
                        v-else-if="sbpError"
                        type="error"
                        variant="tonal"
                    >
                        {{ sbpError }}
                    </v-alert>
                    <template v-else-if="!mobile">
                        <p class="mb-4 text-center">
                            Отсканируйте QR-код в приложении банка
                        </p>
                        <div class="justify-center d-flex">
                            <img
                                v-if="qrDataUri"
                                :src="qrDataUri"
                                alt="QR-код СБП"
                                width="280"
                                height="280"
                            />
                        </div>
                    </template>
                    <template v-else>
                        <v-text-field
                            v-model="bankQuery"
                            label="Поиск банка"
                            variant="outlined"
                            hide-details
                            class="mb-3"
                        />
                        <v-list
                            v-if="filteredBanks.length"
                            max-height="320"
                            class="overflow-y-auto"
                        >
                            <v-list-item
                                v-for="bank in filteredBanks"
                                :key="bank.BankId"
                                :active="selectedBankId === bank.BankId"
                                @click="selectedBankId = bank.BankId"
                            >
                                <template #prepend>
                                    <v-avatar size="32" class="me-2">
                                        <v-img
                                            :src="bank.BankLogo"
                                            :alt="bank.BankName"
                                        />
                                    </v-avatar>
                                </template>
                                <v-list-item-title>{{
                                    bank.BankName
                                }}</v-list-item-title>
                            </v-list-item>
                        </v-list>
                        <p v-else class="text-medium-emphasis">
                            Банки не найдены
                        </p>
                    </template>
                </v-card-text>
                <v-card-actions>
                    <v-btn
                        v-if="mobile && !sbpError"
                        color="primary"
                        :disabled="!selectedBankId || sbpLoading"
                        :loading="deeplinkLoading"
                        @click="payInBankApp"
                    >
                        Оплатить
                    </v-btn>
                    <v-btn @click="closeSbpDialog">Закрыть</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
