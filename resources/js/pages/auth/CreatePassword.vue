<script setup>
    import axios from 'axios';
	import AuthLayout from '@/layouts/AuthLayout.vue';
	import { useToastsStore } from '@/stores/toasts';
	import { useForm } from 'laravel-precognition-vue';
	import { useRoute } from 'vue-router';
	import { useRoute as useZiggyRoute } from 'ziggy-js';

	const toastsStore = useToastsStore();
	const currentRoute = useRoute();
	const route = useZiggyRoute();

	const form = useForm('post', route("password.store"), {
		token: currentRoute.params.token,
		email: currentRoute.params.email,
		password: null,
		password_confirmation: null,
	})
	

    function onSubmit(e) {
        form.submit()
			.then(response => location.href = '/')
			.catch(error => toastsStore.handleResponseError(error))
    }
</script>

<template>
    <AuthLayout>
        <v-responsive class="pa-4">
            <v-card class="px-6 py-8 mx-auto" max-width="344">
                <p class="mb-6 text-center text-title-2">Задайте пароль для входа в аккаунт</p>
				
				<v-form @submit.prevent="onSubmit">
                    <v-text-field class="mb-2" label="Email" name="email" type="email"
                        prepend-inner-icon="mdi-at" v-model="form.email" readonly variant="outlined" 
						:error="form.invalid('email')"
						:error-messages="form.errors.email"
					></v-text-field>
						
                    <v-text-field class="mb-2" label="Пароль" name="password" type="password" variant="outlined"
                        prepend-inner-icon="mdi-lock-outline" v-model="form.password"
						:error="form.invalid('password')"
						:error-messages="form.errors.password"	
					></v-text-field>
						
                    <v-text-field class="mb-2" label="Повторите пароль" name="password_confirmation" type="password"
                        prepend-inner-icon="mdi-lock-outline" variant="outlined" v-model="form.password_confirmation"></v-text-field>

                    <br>

                    <v-btn :disabled="form.processing" :loading="form.processing" color="primary" size="large" type="submit"
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
