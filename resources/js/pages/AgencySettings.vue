<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import { useForm } from "laravel-precognition-vue";
	import { useUserStore } from "@/stores/user";
	import { useToastsStore } from "@/stores/toasts";

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const snoEnum = [
		{title: 'ОСН', props: { subtitle: 'Общая система налогооблажания' }, value: 'osn'},
		{title: 'УСН Доход', props: { subtitle: 'Упрощенная система налогооблажения (доходы)' }, value: 'usn_income'},
		{title: 'УСН Доход-Расход', props: { subtitle: 'Упрощенная система налогооблажения (доходы минус расходы)' }, value: 'usn_income_outcome'},
		{title: 'ЕНВД', props: { subtitle: 'Единый налог на вмененный доход' }, value: 'envd'},
		{title: 'ЕСН', props: { subtitle: 'Единый сельскохозяйственный налог' }, value: 'esn'},
		{title: 'Патент', props: { subtitle: '' }, value: 'patent'},
	];

	const detailsForm = useForm("post", route("agency.update-details"), {
		name: userStore.user.agency.name,
		inn: userStore.user.agency.inn,
		email: userStore.user.agency.email,
		sno: userStore.user.agency.sno,
		payment_address: userStore.user.agency.payment_address,
	});

	const submitDetailsForm = () => detailsForm.submit()
		.then(response => {
			userStore.setAgency(response.data);

			toastsStore.addSuccess("Данные успешно обновлены", 2500);
			detailsForm.errors = {};
		})
		.catch(error => {
			toastsStore.handleResponseError(error);
		});
</script>

<template>
	<AppLayout>
		<div class="w-100">
			<H1>Настройки агентства</H1>

			<div>
				<form @submit.prevent="submitDetailsForm" class="max-w-[600px] mx-auto d-flex flex-col ga-3 mb-12">
					<v-text-field
						clearable
						label="Название организации"
						v-model="detailsForm.name"
						variant="outlined"
						:hint="detailsForm.errors.name"
						persistent-hint
						:class="detailsForm.invalid('name') ? 'text-danger' : ''"
					></v-text-field>

					<v-text-field
						clearable
						label="ИНН"
						v-model="detailsForm.inn"
						variant="outlined"
						:hint="detailsForm.errors.inn"
						persistent-hint
						:class="detailsForm.invalid('inn') ? 'text-danger' : ''"
					></v-text-field>

					<v-text-field
						clearable
						label="Email"
						v-model="detailsForm.email"
						variant="outlined"
						type="email"
						:hint="detailsForm.errors.email"
						persistent-hint
						:class="detailsForm.invalid('email') ? 'text-danger' : ''"
					></v-text-field>

					<v-select
						label="Система налогооблажения"
						:items="snoEnum"
						variant="outlined"
						v-model="detailsForm.sno"
						:hint="detailsForm.errors.sno"
						persistent-hint
						:class="detailsForm.invalid('sno') ? 'text-danger' : ''"
					></v-select>
					
					<v-text-field
						clearable
						label="Место расчетов"
						v-model="detailsForm.payment_address"
						variant="outlined"
						:hint="detailsForm.errors.payment_address"
						persistent-hint
						:class="detailsForm.invalid('payment_address') ? 'text-danger' : ''"
					></v-text-field>

					<v-btn variant="outlined" type="submit"
						color="primary"
						block
						:loading="detailsForm.processing"
						:disabled="detailsForm.processing"
					>Сохранить</v-btn>
				</form>
			</div>
		</div>
	</AppLayout>
</template>
