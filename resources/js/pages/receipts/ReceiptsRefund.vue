<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
	import { reactive, ref } from 'vue';
	import { useToastsStore } from '@/stores/toasts';
	import axios from 'axios';

	const toastsStore = useToastsStore();

	const receipt = ref(null);
	const loading = ref(null);
	const form = ref(null);

	function loadReceipt(event) {
		let data = new FormData(event.target);

		loading.value = true;

		axios.get(route('receipts.index'), {params: {
			filters: [
				{ column: 'fn_number', value: data.get('fn_number') },
				{ column: 'fiscal_document_number', value: data.get('fiscal_document_number') },
				{ column: 'fiscal_document_attribute', value: data.get('fiscal_document_attribute') },
				{ column: 'status', value: 'done' },
				{ column: 'receipt_type', value: 'sell' },
			]
		}})
			.then(response => {
				if (response.data.total === 0) {
					toastsStore.addError('Чек не найден', 2500);
					return;
				}

				receipt.value = response.data.data[0];
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => {
				loading.value = false;
			})
	}


	const refundModal = ref(false);

	function makeRefund() {
		axios.post(route('receipts.refund', receipt.value.id))
			.then(response => {
				receipt.value = null;
				form.value.reset();
				toastsStore.addSuccess('Чек возврата успешно оформлен', 2500);
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => refundModal.value = false)
	}
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Возврат чека</H1>
			</template>
			<template v-slot:content>
				<form @submit.prevent="loadReceipt" ref="form">
					<div class="d-flex ga-2">
						<v-text-field name="fn_number" label="Номер ФН" variant="outlined" />
						<v-text-field name="fiscal_document_number" label="Фискальный номер документа" variant="outlined" />
						<v-text-field name="fiscal_document_attribute" label="Фискальный признак документа" variant="outlined" />
					</div>

					<div class="justify-center d-flex">
						<v-btn type="submit" color="primary" :loading="loading">Найти</v-btn>
					</div>
				</form>

				<div class="justify-center mt-6 d-flex" v-if="receipt">
					<v-btn color="danger" @click="refundModal = true">Оформить возврат</v-btn>
				</div>

				<div class="justify-center mt-6 d-flex">
					<ReceiptDetails :receipt="receipt" width="600px" />
				</div>
			</template>
		</CrudPage>

		<v-dialog
			v-model="refundModal"
			width="auto"
			min-width="400"
			max-width="800"
		>
			<v-card>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Возврат по чеку
						<v-btn icon="mdi-close" variant="plain" @click="refundModal = false"></v-btn>
					</div>
				</template>

				<v-card-text>
					Оформить возврат на сумму {{ receipt?.amount.toLocaleString() }}?
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="makeRefund" color="danger">Оформить возврат</v-btn>
					<v-btn @click="refundModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
	</AppLayout>
</template>
