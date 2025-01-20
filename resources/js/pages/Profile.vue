<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import { useForm } from "laravel-precognition-vue";
import { useUserStore } from "@/stores/user";
import { useToastsStore } from "@/stores/toasts";
import { ref } from "vue";

const userStore = useUserStore();
const toastsStore = useToastsStore();

const profileForm = useForm("post", route("user.update"), {
  name: userStore.user.name,
  email: userStore.user.email,
});

const submitProfileForm = () => profileForm.submit()
	.then(response => {
		userStore.setUser(response.data);

		toastsStore.addSuccess("Профиль успешно обновлен", 2500);
		profileForm.errors = {};
	})
	.catch(error => {
		toastsStore.handleResponseError(error);
	});

const passwordForm = useForm("post", route("user.password"), {
  password: null,
  password_confirmation: null,
});
const showPassword = ref(false);
const showPasswordConfirm = ref(false);

const submitPasswordForm = () => passwordForm.submit()
	.then(response => {
		toastsStore.addSuccess("Пароль успешно обновлен", 2500);
		passwordForm.forgetError('password');
	})
	.catch(error => {
		toastsStore.handleResponseError(error);
	});
</script>

<template>
  <AppLayout>
    <div class="w-100">
		<H1>Профиль</H1>

		<div>
			<form @submit.prevent="submitProfileForm" class="max-w-[600px] mx-auto d-flex flex-col ga-3 mb-12">
				<v-text-field
					clearable
					label="Имя"
					v-model="profileForm.name"
					variant="outlined"
					:hint="profileForm.errors.name"
					persistent-hint
					:class="profileForm.invalid('name') ? 'text-danger' : ''"
				></v-text-field>

				<v-text-field
					clearable
					label="Email"
					v-model="profileForm.email"
					variant="outlined"
					disabled
					type="email"
				></v-text-field>

				<v-btn variant="outlined" type="submit"
					color="primary"
					block
					:loading="profileForm.processing"
					:disabled="profileForm.processing"
				>Сохранить</v-btn>
			</form>

			<h2 class="my-3 text-center text-h5">Изменить пароль</h2>

			<form @submit.prevent="submitPasswordForm" class="max-w-[600px] mx-auto d-flex flex-col ga-3">
				<v-text-field
					clearable
					label="Пароль"
					v-model="passwordForm.password"
					variant="outlined"
					:class="passwordForm.invalid('password') ? 'text-danger' : ''"
					:hint="passwordForm.errors.password"
					persistent-hint
					:append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
					:type="showPassword ? 'text' : 'password'"
					@click:append-inner="showPassword = !showPassword"
				>
				</v-text-field>

				<v-text-field
					clearable
					label="Повторите пароль"
					v-model="passwordForm.password_confirmation"
					variant="outlined"
					:append-inner-icon="showPasswordConfirm ? 'mdi-eye' : 'mdi-eye-off'"
					:type="showPasswordConfirm ? 'text' : 'password'"
					@click:append-inner="showPasswordConfirm = !showPasswordConfirm"
				></v-text-field>

				<v-btn variant="outlined" color="green" type="submit" block
					:loading="passwordForm.processing"
					:disabled="passwordForm.processing"
				>Изменить пароль</v-btn>
			</form>
		</div>
    </div>
  </AppLayout>
</template>
