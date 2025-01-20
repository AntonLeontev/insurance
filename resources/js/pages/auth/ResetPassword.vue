<script setup>
    import axios from 'axios';
	import AuthLayout from '@/layouts/AuthLayout.vue';
	import { useToastsStore } from '@/stores/toasts';

    import { ref } from 'vue';

    const loading = ref(false);
    const toastsStore = useToastsStore();
	const params = new URLSearchParams(window.location.search);

    function onSubmit(e) {
        loading.value = true;

        axios.post('reset-password', new FormData(e.target))
			.then(response => {
				toastsStore.addSuccess('Пароль успешно изменен')
			})
			.catch(error => toastsStore.addError(error.response?.data?.message ?? error.message))
			.finally(() => loading.value = false)
    }
</script>

<template>
    <AuthLayout>
        <v-responsive class="pa-4">
            <v-card class="px-6 py-8 mx-auto" max-width="344">
                <v-form @submit.prevent="onSubmit">
					<input type="hidden" name="token" :value="params.get('token')">
					<input type="hidden" name="email" :value="params.get('email')">
                    <v-text-field class="mb-2" label="Пароль" name="password" type="password"
                        prepend-inner-icon="mdi-lock-outline"></v-text-field>
                    <v-text-field class="mb-2" label="Повторите пароль" name="password_confirmation" type="password"
                        prepend-inner-icon="mdi-lock-outline"></v-text-field>

					<div class="mt-n3 text-end">
						<router-link :to="{name: 'login'}" class="underline text-caption hover-blue">Вход на сайт</router-link>
					</div>

                    <br>

                    <v-btn :disabled="loading" :loading="loading" color="primary" size="large" type="submit"
                        variant="elevated" block>
                        Сохранить пароль
                    </v-btn>
                </v-form>
            </v-card>
        </v-responsive>
    </AuthLayout>
</template>

<style scoped>
.hover-blue {
	transition: color .3s ease;
}
.hover-blue:hover {
	color: aquamarine;
}
</style>
