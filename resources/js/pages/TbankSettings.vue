<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import { useForm } from "laravel-precognition-vue";
import { useUserStore } from "@/stores/user";
import { useToastsStore } from "@/stores/toasts";
import { ref, onMounted } from "vue";
import axios from "axios";

const userStore = useUserStore();
const toastsStore = useToastsStore();
const loading = ref(false);
const submitting = ref(false);

const tbankForm = useForm(
    "post",
    route("agency.update-tbank", userStore.activeAgency.id),
    {
        terminal: null,
        password: "",
    },
);

const loadCredentials = async () => {
    loading.value = true;
    try {
        const response = await axios.get(
            route("agency.get-tbank", userStore.activeAgency.id),
        );
        tbankForm.terminal = response.data.terminal || null;
        tbankForm.password = ""; // Пароль не возвращается с бэкенда
    } catch (error) {
        toastsStore.handleResponseError(error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadCredentials();
});

const submitTbankForm = () => {
    submitting.value = true;

    // Отправляем пароль только если он был изменен (не пустой)
    const data = {
        terminal: tbankForm.terminal,
    };

    if (tbankForm.password && tbankForm.password.trim() !== "") {
        data.password = tbankForm.password;
    }

    // Используем axios напрямую, так как useForm может не работать с условными полями
    axios
        .post(route("agency.update-tbank", userStore.activeAgency.id), data)
        .then((response) => {
            toastsStore.addSuccess("Данные успешно обновлены", 2500);
            tbankForm.errors = {};
            tbankForm.terminal = response.data.terminal;
            tbankForm.password = ""; // Очищаем поле пароля после успешного сохранения
        })
        .catch((error) => {
            if (error.response?.data?.errors) {
                tbankForm.errors = error.response.data.errors;
            }
            toastsStore.handleResponseError(error);
        })
        .finally(() => {
            submitting.value = false;
        });
};
</script>

<template>
    <AppLayout>
        <div class="w-100">
            <H1>Настройки реквизитов Тбанка</H1>

            <div>
                <form
                    @submit.prevent="submitTbankForm"
                    class="max-w-[600px] mx-auto d-flex flex-col ga-3 mb-12"
                >
                    <v-text-field
                        clearable
                        label="Терминал"
                        v-model="tbankForm.terminal"
                        variant="outlined"
                        :hint="tbankForm.errors.terminal"
                        persistent-hint
                        :class="
                            tbankForm.invalid('terminal') ? 'text-danger' : ''
                        "
                        :loading="loading"
                        autocomplete="off"
                    ></v-text-field>

                    <v-text-field
                        clearable
                        label="Пароль"
                        v-model="tbankForm.password"
                        variant="outlined"
                        :hint="
                            tbankForm.errors.password ||
                            'Оставьте пустым, если не хотите изменять пароль'
                        "
                        persistent-hint
                        :class="
                            tbankForm.invalid('password') ? 'text-danger' : ''
                        "
                        :loading="loading"
                        autocomplete="off"
                    ></v-text-field>

                    <v-btn
                        variant="outlined"
                        type="submit"
                        color="primary"
                        block
                        :loading="submitting || loading"
                        :disabled="submitting || loading"
                        >Сохранить</v-btn
                    >
                </form>
            </div>
        </div>
    </AppLayout>
</template>
