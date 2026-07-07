<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import CrudPage from "@/components/CrudPage.vue";
import { reactive, ref, onMounted } from "vue";
import axios from "axios";
import { useForm } from "laravel-precognition-vue";
import { useUserStore } from "@/stores/user";
import { useToastsStore } from "@/stores/toasts";

const userStore = useUserStore();
const toastsStore = useToastsStore();

const credentials = reactive([]);
const insurers = reactive([]);
const loading = ref(false);

const ffdEnum = [
    { title: "ФФД 1.05", value: "ffd1.05" },
    { title: "ФФД 1.2", value: "ffd1.2" },
];

const snoEnum = [
    { title: "ОСН", value: "osn" },
    { title: "УСН Доход", value: "usn_income" },
    { title: "УСН Доход-Расход", value: "usn_income_outcome" },
    { title: "ЕНВД", value: "envd" },
    { title: "ЕСН", value: "esn" },
    { title: "Патент", value: "patent" },
];

onMounted(() => {
    loadCredentials();
    loadInsurers();
});

function loadCredentials() {
    loading.value = true;
    axios
        .get(
            route("fiscal-credentials.index", {
                agency_id: userStore.activeAgency.id,
            }),
        )
        .then((response) => {
            credentials.splice(0, credentials.length, ...response.data);
        })
        .catch((error) => toastsStore.handleResponseError(error))
        .finally(() => {
            loading.value = false;
        });
}

function loadInsurers() {
    axios
        .get(route("insurers.index", { agency_id: userStore.activeAgency.id }))
        .then((response) => {
            insurers.splice(0, insurers.length, ...response.data);
        });
}

const addModal = ref(false);
const saveForm = useForm(
    "post",
    route("fiscal-credentials.store"),
    emptyFormData(),
);

function emptyFormData() {
    return {
        agency_id: userStore.activeAgency.id,
        name: "",
        is_default: false,
        inn: "",
        email: "",
        sno: null,
        payment_address: "",
        receipt_email: "",
        atol_login: "",
        atol_password: "",
        ffd: null,
        group_code: "",
        terminal: null,
        password: "",
        insurer_ids: [],
    };
}

function openAddModal() {
    saveForm.reset();
    saveForm.agency_id = userStore.activeAgency.id;
    addModal.value = true;
}

function saveCredential() {
    saveForm
        .submit()
        .then(() => {
            loadCredentials();
            loadInsurers();
            addModal.value = false;
            toastsStore.addSuccess("Реквизиты сохранены", 2500);
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

const editModal = ref(false);
const editingId = ref(null);
const editForm = reactive({
    data: emptyFormData(),
    errors: {},
    processing: false,
});

function openEditModal(item) {
    editingId.value = item.id;
    editForm.data = {
        name: item.name,
        inn: item.inn,
        email: item.email,
        sno: item.sno,
        payment_address: item.payment_address,
        receipt_email: item.receipt_email ?? "",
        atol_login: item.atol_login,
        atol_password: "",
        ffd: item.ffd,
        group_code: item.group_code,
        terminal: item.terminal,
        password: "",
        insurer_ids: item.insurers?.map((i) => i.id) ?? [],
    };
    editForm.errors = {};
    editModal.value = true;
}

function editCredential() {
    editForm.processing = true;
    axios
        .put(route("fiscal-credentials.update", editingId.value), editForm.data)
        .then(() => {
            loadCredentials();
            loadInsurers();
            editModal.value = false;
            toastsStore.addSuccess("Реквизиты обновлены", 2500);
        })
        .catch((error) => {
            editForm.errors = error.response?.data?.errors ?? {};
            toastsStore.handleResponseError(error);
        })
        .finally(() => {
            editForm.processing = false;
        });
}

const deleteModal = ref(false);
const selectedCredential = ref(null);

function openDeleteModal(item) {
    selectedCredential.value = item;
    deleteModal.value = true;
}

function deleteCredential() {
    axios
        .delete(
            route("fiscal-credentials.destroy", selectedCredential.value.id),
        )
        .then(() => {
            loadCredentials();
            deleteModal.value = false;
            toastsStore.addSuccess("Реквизиты удалены", 2500);
        })
        .catch((error) => toastsStore.handleResponseError(error));
}

const setDefaultModal = ref(false);
const defaultCredential = ref(null);
const setDefaultProcessing = ref(false);

function openSetDefaultModal(item) {
    defaultCredential.value = item;
    setDefaultModal.value = true;
}

function setDefault() {
    setDefaultProcessing.value = true;
    axios
        .post(route("fiscal-credentials.set-default", defaultCredential.value.id))
        .then(() => {
            loadCredentials();
            setDefaultModal.value = false;
            toastsStore.addSuccess("Реквизиты по умолчанию изменены", 2500);
        })
        .catch((error) => toastsStore.handleResponseError(error))
        .finally(() => {
            setDefaultProcessing.value = false;
        });
}
</script>

<template>
    <AppLayout>
        <CrudPage>
            <template #header>
                <div class="justify-between d-flex w-100">
                    <H1>Фискальные реквизиты</H1>
                    <v-btn
                        prepend-icon="mdi-plus"
                        color="primary"
                        @click="openAddModal"
                        >Добавить реквизиты</v-btn
                    >
                </div>
            </template>

            <template #content>
                <v-progress-linear v-if="loading" indeterminate />

                <v-table v-else density="comfortable">
                    <thead>
                        <tr>
                            <th>Наименование</th>
                            <th>ИНН</th>
                            <th>Страховые</th>
                            <th>Терминал</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in credentials" :key="item.id">
                            <td>
                                {{ item.name }}
                                <v-chip
                                    v-if="item.is_default"
                                    size="x-small"
                                    color="primary"
                                    class="ms-2"
                                    >Основные</v-chip
                                >
                            </td>
                            <td>{{ item.inn }}</td>
                            <td>
                                <v-chip
                                    v-for="insurer in item.insurers"
                                    :key="insurer.id"
                                    size="small"
                                    class="mb-1 me-1"
                                    >{{ insurer.name }}</v-chip
                                >
                                <span
                                    v-if="!item.insurers?.length"
                                    class="text-medium-emphasis"
                                    >—</span
                                >
                            </td>
                            <td>{{ item.terminal ? "Подключён" : "—" }}</td>
                            <td class="text-end">
                                <div class="flex justify-end items-center">
                                    <v-btn
                                        v-if="!item.is_default"
                                        size="small"
                                        variant="text"
                                        @click="openSetDefaultModal(item)"
                                        >Сделать основными</v-btn
                                    >
                                    <v-btn
                                        icon="mdi-pencil"
                                        variant="text"
                                        size="small"
                                        @click="openEditModal(item)"
                                    />
                                    <v-btn
                                        icon="mdi-delete"
                                        variant="text"
                                        size="small"
                                        color="error"
                                        :disabled="item.is_default"
                                        @click="openDeleteModal(item)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </v-table>

                <v-empty-state
                    v-if="!loading && credentials.length === 0"
                    icon="mdi-cash-register"
                    title="Реквизиты не найдены"
                />
            </template>
        </CrudPage>

        <v-dialog v-model="addModal" max-width="700" scrollable>
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    Добавить реквизиты
                    <v-btn
                        icon="mdi-close"
                        variant="plain"
                        @click="addModal = false"
                    />
                </v-card-title>
                <v-card-text>
                    <form
                        class="d-flex flex-column ga-2"
                        @submit.prevent="saveCredential"
                    >
                        <v-text-field
                            v-model="saveForm.name"
                            label="Наименование юр. лица"
                            variant="outlined"
                            :error="saveForm.invalid('name')"
                            :error-messages="saveForm.errors.name"
                        />
                        <v-text-field
                            v-model="saveForm.inn"
                            label="ИНН"
                            variant="outlined"
                            :error="saveForm.invalid('inn')"
                            :error-messages="saveForm.errors.inn"
                        />
                        <v-text-field
                            v-model="saveForm.email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            :error="saveForm.invalid('email')"
                            :error-messages="saveForm.errors.email"
                        />
                        <v-select
                            v-model="saveForm.sno"
                            :items="snoEnum"
                            item-title="title"
                            item-value="value"
                            label="Система налогообложения"
                            variant="outlined"
                            :error="saveForm.invalid('sno')"
                            :error-messages="saveForm.errors.sno"
                        />
                        <v-text-field
                            v-model="saveForm.payment_address"
                            label="Место расчётов"
                            variant="outlined"
                            :error="saveForm.invalid('payment_address')"
                            :error-messages="saveForm.errors.payment_address"
                        />
                        <v-text-field
                            v-model="saveForm.receipt_email"
                            label="Email для пробитых чеков"
                            variant="outlined"
                            :error="saveForm.invalid('receipt_email')"
                            :error-messages="saveForm.errors.receipt_email"
                        />
                        <v-divider class="my-2" />
                        <div class="text-subtitle-2">АТОЛ</div>
                        <v-text-field
                            v-model="saveForm.group_code"
                            label="Код группы"
                            variant="outlined"
                            :error="saveForm.invalid('group_code')"
                            :error-messages="saveForm.errors.group_code"
                        />
                        <v-text-field
                            v-model="saveForm.atol_login"
                            label="Логин АТОЛ"
                            variant="outlined"
                            :error="saveForm.invalid('atol_login')"
                            :error-messages="saveForm.errors.atol_login"
                        />
                        <v-text-field
                            v-model="saveForm.atol_password"
                            label="Пароль АТОЛ"
                            variant="outlined"
                            :error="saveForm.invalid('atol_password')"
                            :error-messages="saveForm.errors.atol_password"
                        />
                        <v-select
                            v-model="saveForm.ffd"
                            :items="ffdEnum"
                            item-title="title"
                            item-value="value"
                            label="ФФД"
                            variant="outlined"
                            :error="saveForm.invalid('ffd')"
                            :error-messages="saveForm.errors.ffd"
                        />
                        <v-divider class="my-2" />
                        <div class="text-subtitle-2">Т-Банк (опционально)</div>
                        <v-text-field
                            v-model="saveForm.terminal"
                            label="Терминал"
                            variant="outlined"
                            clearable
                            :error="saveForm.invalid('terminal')"
                            :error-messages="saveForm.errors.terminal"
                        />
                        <v-text-field
                            v-model="saveForm.password"
                            label="Пароль терминала"
                            variant="outlined"
                            :error="saveForm.invalid('password')"
                            :error-messages="saveForm.errors.password"
                        />
                        <v-divider class="my-2" />
                        <v-select
                            v-model="saveForm.insurer_ids"
                            :items="insurers"
                            item-title="name"
                            item-value="id"
                            label="Привязанные страховые"
                            variant="outlined"
                            multiple
                            chips
                            closable-chips
                        />
                    </form>
                </v-card-text>
                <v-card-actions>
                    <v-btn
                        color="primary"
                        :loading="saveForm.processing"
                        @click="saveCredential"
                        >Сохранить</v-btn
                    >
                    <v-btn @click="addModal = false">Отмена</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="editModal" max-width="700" scrollable>
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    Редактировать реквизиты
                    <v-btn
                        icon="mdi-close"
                        variant="plain"
                        @click="editModal = false"
                    />
                </v-card-title>
                <v-card-text>
                    <form
                        class="d-flex flex-column ga-2"
                        @submit.prevent="editCredential"
                    >
                        <v-text-field
                            v-model="editForm.data.name"
                            label="Наименование юр. лица"
                            variant="outlined"
                            :error-messages="editForm.errors.name"
                        />
                        <v-text-field
                            v-model="editForm.data.inn"
                            label="ИНН"
                            variant="outlined"
                            :error-messages="editForm.errors.inn"
                        />
                        <v-text-field
                            v-model="editForm.data.email"
                            label="Email"
                            type="email"
                            variant="outlined"
                            :error-messages="editForm.errors.email"
                        />
                        <v-select
                            v-model="editForm.data.sno"
                            :items="snoEnum"
                            item-title="title"
                            item-value="value"
                            label="Система налогообложения"
                            variant="outlined"
                            :error-messages="editForm.errors.sno"
                        />
                        <v-text-field
                            v-model="editForm.data.payment_address"
                            label="Место расчётов"
                            variant="outlined"
                            :error-messages="editForm.errors.payment_address"
                        />
                        <v-text-field
                            v-model="editForm.data.receipt_email"
                            label="Email для пробитых чеков"
                            variant="outlined"
                            :error-messages="editForm.errors.receipt_email"
                        />
                        <v-divider class="my-2" />
                        <div class="text-subtitle-2">АТОЛ</div>
                        <v-text-field
                            v-model="editForm.data.group_code"
                            label="Код группы"
                            variant="outlined"
                            :error-messages="editForm.errors.group_code"
                        />
                        <v-text-field
                            v-model="editForm.data.atol_login"
                            label="Логин АТОЛ"
                            variant="outlined"
                            :error-messages="editForm.errors.atol_login"
                        />
                        <v-text-field
                            v-model="editForm.data.atol_password"
                            label="Пароль АТОЛ"
                            hint="Оставьте пустым, чтобы не менять"
                            variant="outlined"
                            :error-messages="editForm.errors.atol_password"
                        />
                        <v-select
                            v-model="editForm.data.ffd"
                            :items="ffdEnum"
                            item-title="title"
                            item-value="value"
                            label="ФФД"
                            variant="outlined"
                            :error-messages="editForm.errors.ffd"
                        />
                        <v-divider class="my-2" />
                        <div class="text-subtitle-2">Т-Банк (опционально)</div>
                        <v-text-field
                            v-model="editForm.data.terminal"
                            label="Терминал"
                            variant="outlined"
                            clearable
                            :error-messages="editForm.errors.terminal"
                        />
                        <v-text-field
                            v-model="editForm.data.password"
                            label="Пароль терминала"
                            hint="Оставьте пустым, чтобы не менять"
                            variant="outlined"
                            :error-messages="editForm.errors.password"
                        />
                        <v-divider class="my-2" />
                        <v-select
                            v-model="editForm.data.insurer_ids"
                            :items="insurers"
                            item-title="name"
                            item-value="id"
                            label="Привязанные страховые"
                            variant="outlined"
                            multiple
                            chips
                            closable-chips
                        />
                    </form>
                </v-card-text>
                <v-card-actions>
                    <v-btn
                        color="primary"
                        :loading="editForm.processing"
                        @click="editCredential"
                        >Сохранить</v-btn
                    >
                    <v-btn @click="editModal = false">Отмена</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="deleteModal" max-width="480">
            <v-card>
                <v-card-title>Удаление реквизитов</v-card-title>
                <v-card-text>
                    Удалить «{{ selectedCredential?.name }}»?
                    <template v-if="selectedCredential?.insurers?.length">
                        <br /><br />
                        Сначала отвяжите привязанные страховые компании.
                    </template>
                </v-card-text>
                <v-card-actions>
                    <v-btn color="error" @click="deleteCredential"
                        >Удалить</v-btn
                    >
                    <v-btn @click="deleteModal = false">Отмена</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="setDefaultModal" max-width="480">
            <v-card>
                <v-card-title>Основные реквизиты</v-card-title>
                <v-card-text>
                    Сделать «{{ defaultCredential?.name }}» основными реквизитами?
                </v-card-text>
                <v-card-actions>
                    <v-btn
                        color="primary"
                        :loading="setDefaultProcessing"
                        @click="setDefault"
                        >Сделать основными</v-btn
                    >
                    <v-btn @click="setDefaultModal = false">Отмена</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </AppLayout>
</template>
