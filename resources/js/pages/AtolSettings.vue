<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import { useForm } from "laravel-precognition-vue";
	import { useUserStore } from "@/stores/user";
	import { useToastsStore } from "@/stores/toasts";

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const ffdEnum = [
		{title: 'ФФД 1.05', value: 'ffd1.05'},
		{title: 'ФФД 1.2', value: 'ffd1.2'},
	];

	const atolForm = useForm("post", route("agency.update-atol"), {
		group_code: userStore.user.agency.group_code,
		ffd: userStore.user.agency.ffd,
		atol_login: userStore.user.agency.atol_login,
		atol_password: userStore.user.agency.atol_password,
	});

	const submitAtolForm = () => atolForm.submit()
		.then(response => {
			userStore.setUser(response.data);

			toastsStore.addSuccess("Данные успешно обновлены", 2500);
			atolForm.errors = {};
		})
		.catch(error => {
			toastsStore.handleResponseError(error);
		});
</script>

<template>
	<AppLayout>
		<div class="w-100">
			<H1>Настройки доступа к Атол</H1>

			<div>
				<form @submit.prevent="submitAtolForm" class="max-w-[600px] mx-auto d-flex flex-col ga-3 mb-12">
					<v-text-field
						clearable
						label="Код группы"
						v-model="atolForm.group_code"
						variant="outlined"
						:hint="atolForm.errors.group_code"
						persistent-hint
						:class="atolForm.invalid('group_code') ? 'text-danger' : ''"
					></v-text-field>

					<v-text-field
						clearable
						label="Логин"
						v-model="atolForm.atol_login"
						variant="outlined"
						:hint="atolForm.errors.atol_login"
						persistent-hint
						:class="atolForm.invalid('atol_login') ? 'text-danger' : ''"
					></v-text-field>

					<v-text-field
						clearable
						label="Пароль"
						v-model="atolForm.atol_password"
						variant="outlined"
						:hint="atolForm.errors.atol_password"
						persistent-hint
						:class="atolForm.invalid('atol_password') ? 'text-danger' : ''"
					></v-text-field>

					<v-select
						label="ФФД"
						:items="ffdEnum"
						variant="outlined"
						v-model="atolForm.ffd"
						:hint="atolForm.errors.ffd"
						persistent-hint
						:class="atolForm.invalid('ffd') ? 'text-danger' : ''"
					></v-select>
					
					<v-btn variant="outlined" type="submit"
						color="primary"
						block
						:loading="atolForm.processing"
						:disabled="submitAtolForm.processing"
					>Сохранить</v-btn>
				</form>
			</div>
		</div>
	</AppLayout>
</template>
