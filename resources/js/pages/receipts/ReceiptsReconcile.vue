<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
	import { nextTick, onMounted, onUnmounted, ref } from 'vue';
	import { useToastsStore } from '@/stores/toasts';
	import { useUserStore } from '@/stores/user';
	import { parseFiscalQr } from '@/utils/parseFiscalQr';
	import axios from 'axios';

	const toastsStore = useToastsStore();
	const userStore = useUserStore();

	const receipt = ref(null);
	const loading = ref(false);
	const checking = ref(false);
	const fnNumber = ref('');
	const fiscalDocumentNumber = ref('');
	const fiscalDocumentAttribute = ref('');
	const qrInput = ref('');
	const qrTextarea = ref(null);

	function focusQrInput() {
		nextTick(() => {
			qrTextarea.value?.focus();
		});
	}

	function resetForm() {
		qrInput.value = '';
		fnNumber.value = '';
		fiscalDocumentNumber.value = '';
		fiscalDocumentAttribute.value = '';
		receipt.value = null;
		focusQrInput();
	}

	function onWindowKeydown(event) {
		if (event.key === 'Escape') {
			event.preventDefault();
			resetForm();
			return;
		}

		if (event.key !== 'Enter' || event.shiftKey || event.isComposing) {
			return;
		}

		if (receipt.value && !checking.value && !loading.value) {
			event.preventDefault();
			checkReceipt();
		}
	}

	onMounted(() => {
		focusQrInput();
		window.addEventListener('keydown', onWindowKeydown);
	});

	onUnmounted(() => {
		window.removeEventListener('keydown', onWindowKeydown);
	});

	function applyParsedFiscalQr(parsed) {
		fnNumber.value = parsed.fn_number;
		fiscalDocumentNumber.value = parsed.fiscal_document_number;
		fiscalDocumentAttribute.value = parsed.fiscal_document_attribute;
	}

	function onQrPaste(event) {
		const text = event.clipboardData?.getData('text') ?? '';
		const parsed = parseFiscalQr(text);

		if (!parsed) {
			return;
		}

		event.preventDefault();
		qrInput.value = text.trim();
		applyParsedFiscalQr(parsed);
		searchReceipt(parsed);
	}

	function searchReceipt(values = null) {
		let parsed = values;

		if (!parsed && qrInput.value) {
			parsed = parseFiscalQr(qrInput.value);

			if (parsed) {
				applyParsedFiscalQr(parsed);
			}
		}

		const fn = parsed?.fn_number ?? fnNumber.value;
		const documentNumber = parsed?.fiscal_document_number ?? fiscalDocumentNumber.value;
		const documentAttribute = parsed?.fiscal_document_attribute ?? fiscalDocumentAttribute.value;

		loading.value = true;
		receipt.value = null;

		axios.get(route('receipts.index'), { params: {
			agency_id: userStore.activeAgency.id,
			filters: [
				{ column: 'fn_number', value: fn },
				{ column: 'fiscal_document_number', value: documentNumber },
				{ column: 'fiscal_document_attribute', value: documentAttribute },
			],
		}})
			.then(response => {
				if (response.data.total === 0) {
					toastsStore.addError('Чек не найден', 2500);
					return;
				}

				if (response.data.total > 1) {
					toastsStore.addError('Найдено несколько чеков', 2500);
					return;
				}

				receipt.value = response.data.data[0];
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => {
				loading.value = false;
			});
	}

	function checkReceipt() {
		if (!receipt.value || checking.value) {
			return;
		}

		checking.value = true;

		axios.post(route('receipts.check', receipt.value.id))
			.then(() => {
				toastsStore.addSuccess('Чек отмечен', 2500);
				resetForm();
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => {
				checking.value = false;
			});
	}

	function uncheckReceipt() {
		if (!receipt.value || checking.value) {
			return;
		}

		checking.value = true;

		axios.post(route('receipts.uncheck', receipt.value.id))
			.then(response => {
				receipt.value = response.data;
				toastsStore.addSuccess('Отметка снята', 2500);
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => {
				checking.value = false;
			});
	}
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Сверка чеков</H1>
			</template>
			<template v-slot:content>
				<form @submit.prevent="searchReceipt()">
					<div class="d-flex ga-2">
						<v-text-field v-model="fnNumber" name="fn_number" label="Номер ФН" variant="outlined" />
						<v-text-field v-model="fiscalDocumentNumber" name="fiscal_document_number" label="ФНД" variant="outlined" />
						<v-text-field v-model="fiscalDocumentAttribute" name="fiscal_document_attribute" label="ФПД" variant="outlined" />
					</div>

					<v-textarea
						ref="qrTextarea"
						v-model="qrInput"
						label="QR-строка сканера"
						variant="outlined"
						rows="2"
						auto-grow
						hide-details
						class="mt-2"
						@paste="onQrPaste"
					/>

					<div class="justify-center mt-4 d-flex ga-2">
						<v-btn type="submit" color="primary" :loading="loading">Найти чек</v-btn>
						<v-btn type="button" variant="outlined" @click="resetForm">Очистить (Esc)</v-btn>
					</div>
				</form>

				<div class="justify-center mt-6 d-flex ga-2" v-if="receipt">
					<v-btn color="primary" :loading="checking" @click="checkReceipt" v-if="!receipt.is_checked">Отметить чек (Enter)</v-btn>
					<v-btn
						v-if="receipt.is_checked"
						color="secondary"
						variant="outlined"
						:loading="checking"
						@click="uncheckReceipt"
					>
						Снять отметку
					</v-btn>
				</div>

				<div class="justify-center mt-6 d-flex">
					<ReceiptDetails :receipt="receipt" width="600px" />
				</div>
			</template>
		</CrudPage>
	</AppLayout>
</template>
