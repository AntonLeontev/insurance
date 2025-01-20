<script setup>
    import axios from 'axios';
	import AuthLayout from '@/layouts/AuthLayout.vue';
	import { useUserStore } from '@/stores/user';
	import { useToastsStore } from '@/stores/toasts';
	import router from '@/router';

    import { ref } from 'vue';

    const loading = ref(false);
	const userStore = useUserStore();
	const toastsStore = useToastsStore();

    function onSubmit(event) {
        loading.value = true;

        axios.post('login', new FormData(event.target))
			.then(response => {
				userStore.user = response.data

				router.push({name: 'home'})
			})
			.catch(err => {
				toastsStore.addError(err.response?.data?.message ?? err.message)
			})
			.finally(() => {
				loading.value = false
			})
    }
</script>

<template>
    <AuthLayout>
        <v-responsive class="pa-4">
            <v-card class="px-6 py-8 mx-auto" max-width="344">
                <v-form @submit.prevent="onSubmit">
                    <v-text-field class="mb-2" label="Email" name="email" type="email"
                        prepend-inner-icon="mdi-at"></v-text-field>

                    <v-text-field :readonly="loading" label="Пароль" type="password" name="password"
                        prepend-inner-icon="mdi-lock-outline"></v-text-field>

					<div class="mt-n3 text-end hover-blue">
						<router-link :to="{name: 'forgot-password'}" class="underline text-caption">Забыли пароль?</router-link>
					</div>

                    <br>

                    <v-btn :disabled="loading" :loading="loading" color="primary" size="large" type="submit"
                        variant="elevated" block>
                        Войти
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
