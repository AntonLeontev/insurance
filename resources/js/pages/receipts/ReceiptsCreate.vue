<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import ReceiptPreview from '@/components/receipts/ReceiptPreview.vue';
	import { reactive, ref } from 'vue';
	import { useForm } from "laravel-precognition-vue";
	import { useUserStore } from "@/stores/user";
	import { useToastsStore } from "@/stores/toasts";
	import { useRouter } from 'vue-router';

	const userStore = useUserStore();
	const toastsStore = useToastsStore();
	const router = useRouter();

	const insurers = reactive([]);
	const selectedInsurer = reactive({});
	const previewShow = ref(false);

	function loadContracts() {
		const insurer = insurers.find(insurer => insurer.id === createForm.insurer_id);

		Object.assign(selectedInsurer, insurer);
		createForm.contract_id = null;
		createForm.insurer_name = insurer.name;
		createForm.insurer_inn = insurer.inn;
	}
	function setContractName() {
		const contract = selectedInsurer.contracts.find(contract => contract.id === createForm.contract_id);
		createForm.contract_name = contract.name;
	}
	
	function loadInsurers() {
		axios.get(route('insurers.index', {agency_id: userStore.user.agency_id}))
			.then(response => {
				Object.assign(insurers, response.data);
			})
	}
	
	loadInsurers();

	const createForm = useForm("post", route("receipts.store"), {
		agency_id: userStore.user.agency_id,
		name: null,
		surname: null,
		patronymic: null,
		passport: null,
		insurer_id: null,
		contract_id: null,
		contract_series: null,
		contract_number: null,
		client_email: null,
		agent_email: null,
		amount: null,
		payment_type: null,
		is_draft: true,
	});

	function saveAsDraft() {
		createForm.is_draft = true;

		createForm.submit()
			.then(response => {
				toastsStore.addSuccess("Сохранено в черновиках", 2500);
				createForm.errors = {};
				createForm.reset();
			})
			.catch(error => {
				toastsStore.handleResponseError(error);
			});
	} 

	function clearForm() {
		createForm.reset();
		selectedInsurer.contracts = []
	}

	function previewWithCash() {
		createForm.touch(['name', 'surname', 'patronymic', 'passport', 'insurer_id', 'contract_id', 'contract_series', 'contract_number', 'client_email', 'agent_email', 'amount'])
			.validate({
				onSuccess: (response) => {
					previewShow.value = true;
					createForm.payment_type = 'cash';
				},
			});
	}
	function previewWithoutCash() {
		createForm.touch(['name', 'surname', 'patronymic', 'passport', 'insurer_id', 'contract_id', 'contract_series', 'contract_number', 'client_email', 'agent_email', 'amount'])
			.validate({
				onSuccess: (response) => {
					previewShow.value = true;
					createForm.payment_type = 'cashless';
				},
			});
	}	
	function onSubmitted() {
		toastsStore.addSuccess('Чек успешно отправлен в Атол', 2500);
		router.push({name: 'receipts.submitted'});
	}
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Новый чек</H1>
			</template>

			<template v-slot:content>
				<div class="justify-center d-flex">
					<form class="max-w-[600px] w-100 mx-auto d-flex flex-col ga-3 mb-12">
						<v-text-field
							label="Фамилия клиента"
							v-model="createForm.surname"
							variant="outlined"
							:error="createForm.invalid('surname')"
							:error-messages="createForm.errors.surname"
							persistent-hint
						></v-text-field>
						
						<v-text-field
							label="Имя клиента"
							v-model="createForm.name"
							variant="outlined"
							:error="createForm.invalid('name')"
							:error-messages="createForm.errors.name"
							persistent-hint
						></v-text-field>

						<v-text-field
							label="Отчество клиента"
							v-model="createForm.patronymic"
							variant="outlined"
							:error="createForm.invalid('patronymic')"
							:error-messages="createForm.errors.patronymic"
							persistent-hint
						></v-text-field>

						<v-text-field
							label="Паспорт"
							v-model="createForm.passport"
							variant="outlined"
							:error="createForm.invalid('passport')"
							:error-messages="createForm.errors.passport"
							persistent-hint
						></v-text-field>

						<v-select
							v-model="createForm.insurer_id"
							:items="insurers"
							item-title="name"
							item-value="id"
							label="Страховая компания"
							persistent-hint
							:error="createForm.invalid('insurer_id')"
							:error-messages="createForm.errors.insurer_id"
							variant="outlined"
							@update:modelValue="loadContracts"
						></v-select>

						<v-select
							v-model="createForm.contract_id"
							:items="selectedInsurer.contracts"
							item-title="name"
							item-value="id"
							label="Тип договора"
							persistent-hint
							:error="createForm.invalid('contract_id')"
							:error-messages="createForm.errors.contract_id"
							variant="outlined"
							no-data-text="Выберите страховую компанию"
							@update:modelValue="setContractName"
						></v-select>

						<v-text-field
							label="Серия договора"
							v-model="createForm.contract_series"
							variant="outlined"
							:error="createForm.invalid('contract_series')"
							:error-messages="createForm.errors.contract_series"
							persistent-hint
						></v-text-field>

						<v-text-field
							label="Номер договора"
							v-model="createForm.contract_number"
							variant="outlined"
							:error="createForm.invalid('contract_number')"
							:error-messages="createForm.errors.contract_number"
							persistent-hint
						></v-text-field>

						<v-text-field
							label="Email клиента"
							v-model="createForm.client_email"
							variant="outlined"
							:error="createForm.invalid('client_email')"
							:error-messages="createForm.errors.client_email"
							persistent-hint
							type="email"
						></v-text-field>

						<v-text-field
							label="Email агента"
							v-model="createForm.agent_email"
							variant="outlined"
							:error="createForm.invalid('agent_email')"
							:error-messages="createForm.errors.agent_email"
							persistent-hint
							type="email"
						></v-text-field>

						<v-text-field
							label="Сумма договора"
							v-model="createForm.amount"
							variant="outlined"
							:error="createForm.invalid('amount')"
							:error-messages="createForm.errors.amount"
							persistent-hint
							type="number"
							hide-spin-buttons
						></v-text-field>

						<v-btn color="primary" @click="previewWithCash">Наличная оплата</v-btn>
						<v-btn color="primary" @click="previewWithoutCash">Безналичная оплата</v-btn>
						<v-btn color="warning" variant="outlined" @click="saveAsDraft">Сохранить как черновик</v-btn>
						<v-btn color="danger" variant="outlined" @click="clearForm">Очистить</v-btn>
					</form>
				</div>
			</template>
		</CrudPage>

		<ReceiptPreview :receipt="createForm" :show="previewShow" @close="previewShow = false" @submitted="onSubmitted" />
	</AppLayout>
</template>
