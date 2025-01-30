<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import ReceiptPreview from '@/components/receipts/ReceiptPreview.vue';
	import { reactive, ref } from 'vue';
	import { useUserStore } from "@/stores/user";
	import { useToastsStore } from "@/stores/toasts";
	import { useRoute } from 'vue-router';
	import axios from 'axios';

	const userStore = useUserStore();
	const toastsStore = useToastsStore();
	const curRoute = useRoute();

	const insurers = reactive([]);
	const selectedInsurer = reactive({});
	const previewShow = ref(false);

	const editForm = reactive({ 
		invalid(item) {
			return this.errors[item] !== undefined;
		}, 
		errors: {},
	});

	loadInsurers();
	loadReceipt();

	function loadReceipt() {
		axios.get(route('receipts.show', {id: curRoute.params.id}))
			.then(response => {
				editForm.agency_id = response.data.agency_id;
				editForm.name = response.data.name;
				editForm.surname = response.data.surname;
				editForm.patronymic = response.data.patronymic;
				editForm.passport = response.data.passport;
				editForm.insurer_name = response.data.insurer_name;
				editForm.contract_name = response.data.contract_name;
				editForm.contract_series = response.data.contract_series;
				editForm.contract_number = response.data.contract_number;
				editForm.client_email = response.data.client_email;
				editForm.agent_email = response.data.agent_email;
				editForm.amount = response.data.amount;
				editForm.is_draft = response.data.is_draft;

				const insurer = insurers.find(insurer => insurer.name === editForm.insurer_name);
				Object.assign(selectedInsurer, insurer);
			})
	}

	function loadContracts() {
		const insurer = insurers.find(insurer => insurer.name === editForm.insurer_name);

		Object.assign(selectedInsurer, insurer);
		editForm.contract_name = null;
	}
	
	function loadInsurers() {
		axios.get(route('insurers.index', {agency_id: userStore.user.agency_id}))
			.then(response => {
				Object.assign(insurers, response.data);
			})
	}

	function save() {
		axios.put(route('receipts.update', {id: curRoute.params.id}), editForm)
			.then(response => {
				toastsStore.addSuccess("Сохранено", 2500);
				editForm.errors = {};
			})
			.catch(error => {
				editForm.errors = error.response.data.errors;
				toastsStore.handleResponseError(error);
			});
	} 

	function previewWithCash() {
		previewShow.value = true;
		editForm.payment_type = 'cash';
	}
	function previewWithoutCash() {
		previewShow.value = true;
		editForm.payment_type = 'cashless';
	}
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Редактировать чек</H1>
			</template>

			<template v-slot:content>
				<div class="justify-center d-flex">
					<form class="max-w-[600px] w-100 mx-auto d-flex flex-col ga-3 mb-12">
						<v-text-field
							clearable
							label="Фамилия клиента"
							v-model="editForm.surname"
							variant="outlined"
							:error="editForm.invalid('surname')"
							:error-messages="editForm.errors.surname"
							persistent-hint
						></v-text-field>
						
						<v-text-field
							clearable
							label="Имя клиента"
							v-model="editForm.name"
							variant="outlined"
							:error="editForm.invalid('name')"
							:error-messages="editForm.errors.name"
							persistent-hint
						></v-text-field>

						<v-text-field
							clearable
							label="Отчество клиента"
							v-model="editForm.patronymic"
							variant="outlined"
							:error="editForm.invalid('patronymic')"
							:error-messages="editForm.errors.patronymic"
							persistent-hint
						></v-text-field>

						<v-text-field
							clearable
							label="Паспорт"
							v-model="editForm.passport"
							variant="outlined"
							:error="editForm.invalid('passport')"
							:error-messages="editForm.errors.passport"
							persistent-hint
						></v-text-field>

						<v-select
							v-model="editForm.insurer_name"
							:items="insurers"
							item-title="name"
							item-value="name"
							label="Страховая компания"
							persistent-hint
							:error="editForm.invalid('insurer_name')"
							:error-messages="editForm.errors.insurer_name"
							variant="outlined"
							@update:modelValue="loadContracts"
						></v-select>

						<v-select
							v-model="editForm.contract_name"
							:items="selectedInsurer.contracts"
							item-title="name"
							item-value="name"
							label="Тип договора"
							persistent-hint
							:error="editForm.invalid('contract_name')"
							:error-messages="editForm.errors.contract_name"
							variant="outlined"
							no-data-text="Выберите страховую компанию"
						></v-select>

						<v-text-field
							clearable
							label="Серия договора"
							v-model="editForm.contract_series"
							variant="outlined"
							:error="editForm.invalid('contract_series')"
							:error-messages="editForm.errors.contract_series"
							persistent-hint
						></v-text-field>

						<v-text-field
							clearable
							label="Номер договора"
							v-model="editForm.contract_number"
							variant="outlined"
							:error="editForm.invalid('contract_number')"
							:error-messages="editForm.errors.contract_number"
							persistent-hint
						></v-text-field>

						<v-text-field
							clearable
							label="Email клиента"
							v-model="editForm.client_email"
							variant="outlined"
							:error="editForm.invalid('client_email')"
							:error-messages="editForm.errors.client_email"
							persistent-hint
							type="email"
						></v-text-field>

						<v-text-field
							clearable
							label="Email агента"
							v-model="editForm.agent_email"
							variant="outlined"
							:error="editForm.invalid('agent_email')"
							:error-messages="editForm.errors.agent_email"
							persistent-hint
							type="email"
						></v-text-field>

						<v-text-field
							clearable
							label="Сумма договора"
							v-model="editForm.amount"
							variant="outlined"
							:error="editForm.invalid('amount')"
							:error-messages="editForm.errors.amount"
							persistent-hint
							type="number"
							hide-spin-buttons
						></v-text-field>

						<v-btn color="warning" variant="outlined" @click="save">Сохранить</v-btn>
						<v-btn color="primary" @click="previewWithCash">Наличная оплата</v-btn>
						<v-btn color="primary" @click="previewWithoutCash">Безналичная оплата</v-btn>
					</form>
				</div>
			</template>
		</CrudPage>

		<ReceiptPreview :receipt="editForm" :show="previewShow" @close="previewShow = false" />
	</AppLayout>
</template>
