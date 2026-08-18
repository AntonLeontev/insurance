<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import DataTablePagination from '@/components/DataTablePagination.vue';
	import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
	import ReceiptPaymentsDialog from '@/components/receipts/ReceiptPaymentsDialog.vue';
	import { ref, watch } from 'vue';
	import { useUserStore } from '@/stores/user';
	import { useToastsStore } from '@/stores/toasts';
	import axios from 'axios';
	import { paymentStatusColor, paymentStatusIcon, paymentStatusLabel } from '@/utils/paymentStatus';

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const headers = [
        { title: 'ФИО', align: 'start', key: 'surname' },
        { title: 'Договор', key: 'contract_series', value: item => `${item.contract_series} ${item.contract_number}`, align: 'start', 'maxWidth': '150px' },
        { title: 'Страховая', key: 'insurer_name', align: 'start', minWidth: '200px' },
		{ title: 'Фискальные реквизиты', key: 'fiscal_credential.name', align: 'start' },
		{ title: 'Стоимость', key: 'amount', align: 'start' },
		{ title: 'Статус', key: 'status', align: 'start' },
		{ title: 'Оплата', key: 'payment_status', value: item => paymentStatusLabel(item.payments?.[0]), align: 'start', sortable: false },
		{ title: 'Кассир', key: 'user.email', align: 'start' },
		{ title: 'Сверен', key: 'is_checked', align: 'start' },
		{ title: 'ФН / ФПД / ФНД', key: 'fiscal_marks', align: 'start', sortable: false },
		{ title: 'Действия', key: 'actions', align: 'end', sortable: false }
    ];
	const receipts = ref([]);
	const loading = ref(false);

	const itemsPerPage = ref(localStorage.getItem('receipts-drafts:itemsPerPage') || 10);
	const totalItems = ref(0);
	const page = ref(1);
	const sortBy = ref(null);
	const search = ref(null);
	const checkFilter = ref('all');
	const checkFilterItems = [
		{ title: 'Все', value: 'all' },
		{ title: 'Сверенные', value: 'checked' },
		{ title: 'Не сверенные', value: 'unchecked' },
	];
	const dateFrom = ref(null);
	const dateTo = ref(null);
	let searchTimeout = null;

	watch(itemsPerPage, () => {
		localStorage.setItem('receipts-drafts:itemsPerPage', itemsPerPage.value);
	});
	watch(search, () => {
		clearTimeout(searchTimeout);
		searchTimeout = setTimeout(() => {
			if (page.value !== 1) {
				page.value = 1; // Сбрасываем страницу при поиске, что вызовет @update:options
			} else {
				// Если уже на первой странице, вызываем загрузку вручную
				loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value });
			}
		}, 350);
	});
	watch(checkFilter, () => {
		if (page.value !== 1) {
			page.value = 1;
			return;
		}

		loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value });
	});
	watch([dateFrom, dateTo], () => {
		if (page.value !== 1) {
			page.value = 1;
			return;
		}

		loadItems({ page: 1, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value });
	});

	function loadItems({ page: pageNum, itemsPerPage: perPage, sortBy: sort }) {
		loading.value = true;

		const filters = [{column: 'is_draft', value: 0}];

		if (checkFilter.value === 'checked') {
			filters.push({ column: 'is_checked', value: 1 });
		} else if (checkFilter.value === 'unchecked') {
			filters.push({ column: 'is_checked', value: 0 });
		}

		if (dateFrom.value) {
			filters.push({ column: 'submited_at', operator: '>=', value: dateFrom.value });
		}

		if (dateTo.value) {
			filters.push({ column: 'submited_at', operator: '<=', value: dateTo.value });
		}

		axios.get(
			route('receipts.index'), 
			{ params: { 
				page: pageNum, 
				agency_id: userStore.activeAgency.id,
				items_per_page: perPage, 
				sort: sort,
				search: search.value,
				filters,
			} }
		)
			.then(response => {
				receipts.value = response.data.data;
				totalItems.value = response.data.total;
			})
			.finally(() => {
				loading.value = false;
			})
	}

	function checkTooltip(item) {
		if (!item.is_checked) {
			return 'Не сверен';
		}

		const who = [item.checked_by?.name, item.checked_by?.email].filter(Boolean).join(' / ');
		const when = item.checked_at
			? new Date(item.checked_at).toLocaleString('ru-RU')
			: '';

		return [who, when].filter(Boolean).join(', ') || 'Сверен';
	}

	const selectedReceipt = ref(null);
	const selectedPayments = ref([]);
	const paymentsModal = ref(false);

	function openPaymentsModal(item) {
		if (!item?.payments?.length) {
			return;
		}

		selectedPayments.value = item.payments;
		paymentsModal.value = true;
	}

	const detailsModal = ref(false);
	function openDetailsModal(item) {
		selectedReceipt.value = item
		detailsModal.value = true
	}
	function onReceiptRowClick(_event, { item }) {
		openDetailsModal(item?.raw ?? item)
	}

	function canRefund(item) {
		if (!item) {
			return false;
		}

		const role = userStore.activeAgency?.pivot?.role;

		return item.status === 'done'
			&& item.receipt_type === 'sell'
			&& (role === 'admin' || role === 'senior cashier');
	}

	const refundModal = ref(false);
	const refunding = ref(false);
	function openRefundModal(item) {
		if (refunding.value) {
			return;
		}

		selectedReceipt.value = item
		refundModal.value = true
	}
	function makeRefund() {
		if (refunding.value) {
			return;
		}

		refunding.value = true;

		axios.post(route('receipts.refund', selectedReceipt.value?.id))
			.then(response => loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value }))
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => {
				refundModal.value = false
				refunding.value = false
			})
	}

	const updatingStatus = ref(false);
	function updateStatus(id) {
		if (updatingStatus.value) {
			return;
		}

		updatingStatus.value = true;
		
		axios.get(route('receipts.get-status', id))
			.then(response => {
				let index = receipts.value.findIndex(receipt => receipt.id === id);
				receipts.value[index] = response.data;

				if (selectedReceipt.value?.id === id) {
					selectedReceipt.value = response.data;
				}
			})
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => updatingStatus.value = false)
	}
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Оформленные чеки</H1>

				<div class="justify-start mt-3 d-flex ga-2">
					<v-text-field v-model="search" density="compact" placeholder="Поиск" variant="outlined" hide-details max-width="300px" 
						append-inner-icon="mdi-magnify"
						clearable
					/>
					<v-select
						v-model="checkFilter"
						:items="checkFilterItems"
						label="Сверка"
						density="compact"
						variant="outlined"
						hide-details
						max-width="220px"
					/>
					<v-text-field
						v-model="dateFrom"
						type="date"
						label="От"
						placeholder="Дата пробития"
						density="compact"
						variant="outlined"
						hide-details
						clearable
						:max="dateTo || undefined"
						max-width="180px"
					/>
					<v-text-field
						v-model="dateTo"
						type="date"
						label="До"
						placeholder="Дата пробития"
						density="compact"
						variant="outlined"
						hide-details
						clearable
						:min="dateFrom || undefined"
						max-width="180px"
					/>
				</div>
			</template>

			<template v-slot:content>
				<v-data-table-server
					v-model:items-per-page="itemsPerPage"
					v-model:page="page"
					:headers="headers"
					:items="receipts"
					:items-length="totalItems"
					:loading="loading"
					@update:options="loadItems"
					@click:row="onReceiptRowClick"
					:row-props="() => ({ class: 'cursor-pointer' })"
					hover
					density="comfortable"
					item-key="id"
				>
					<template v-slot:item.surname="{ item }">
						<span class="">
							{{ item.surname }} {{ item.name }} {{ item.patronymic ?? '' }}
						</span>
					</template>
					<template v-slot:item.insurer_name="{ item }">
						<div>
							<div>{{ item.insurer_name }}</div>
							<div class="text-caption text-medium-emphasis">{{ item.contract_name }}</div>
						</div>
					</template>
					<template v-slot:item.fiscal_credential.name="{ item }">
						{{ item.fiscal_credential?.name }}
					</template>
					<template v-slot:item.amount="{ item }">
						<span class="text-nowrap">
							<v-icon icon="mdi-arrow-bottom-left" color="primary" v-if="item.receipt_type === 'sell'" title="Приход" />
							<v-icon icon="mdi-arrow-top-right" color="danger" v-if="item.receipt_type === 'sell refund'" title="Возврат прихода" />
							{{ item.amount.toLocaleString('ru-RU')+' ₽' }}
						</span>
					</template>

					<template v-slot:item.is_checked="{ item }">
						<v-icon
							v-if="item.is_checked"
							icon="mdi-check-circle"
							color="primary"
							v-tooltip:bottom="checkTooltip(item)"
						/>
						<v-icon
							v-else
							icon="mdi-circle-outline"
							v-tooltip:bottom="'Не сверен'"
						/>
					</template>

					<template v-slot:item.status="{ item }">
						<span class="" v-if="item.status === 'wait'">
							<v-icon icon="mdi-timer-sand" />
							В обработке
						</span>
						<span class="flex flex-column align-center" v-if="item.status === 'done'">
							<v-icon icon="mdi-check-circle-outline" color="primary" />
							Успешно
						</span>
						<span class="" v-if="item.status === 'fail'">
							<v-icon icon="mdi-alert" color="danger" />
							Ошибка
							<v-icon icon="mdi-information-outline" color="danger" v-tooltip:top="item.error_text" />
						</span>
					</template>

					<template v-slot:item.payment_status="{ item }">
						<div
							v-if="item.payments?.length"
							class="flex cursor-pointer flex-column align-center"
							@click.stop="openPaymentsModal(item)"
						>
							<v-icon
								:icon="paymentStatusIcon(item.payments[0])"
								:color="paymentStatusColor(item.payments[0])"
							/>
							{{ paymentStatusLabel(item.payments[0]) }}
							<div class="">
								<v-icon icon="mdi-chevron-down" size="small" />
							</div>
						</div>
					</template>

					<template v-slot:item.user.email="{ item }">
						<div>
							<div v-if="item.user?.name">{{ item.user.name }}</div>
							<div :class="item.user?.name ? 'text-caption text-medium-emphasis' : ''">
								{{ item.user?.email }}
							</div>
						</div>
					</template>

					<template v-slot:item.fiscal_marks="{ item }">
						<div>
							<div class="text-caption text-nowrap">ФН: {{ item.fn_number }}</div>
							<div class="text-caption">ФПД: {{ item.fiscal_document_attribute }}</div>
							<div class="text-caption">ФНД: {{ item.fiscal_document_number }}</div>
						</div>
					</template>

					<template v-slot:item.actions="{ item }">
						<div @click.stop>
							<v-icon
								icon="mdi-restart"
								variant="plain"
								size="small"
								class="cursor-pointer me-2"
								v-tooltip:bottom="'Обновить статус'"
								v-if="item.status === 'wait'"
								@click="updateStatus(item.id)"
							></v-icon>
							<v-icon
								icon="mdi-arrow-u-up-right-bold"
								variant="plain"
								size="small"
								class="cursor-pointer me-2"
								color="danger"
								v-tooltip:bottom="'Пробить возврат'"
								v-if="canRefund(item)"
								@click="openRefundModal(item)"
							></v-icon>
							<v-icon
							class="cursor-pointer"
								size="small"
								@click="openDetailsModal(item)"
								color="primary"
								v-tooltip:bottom="'Подробнее'"
							>
								mdi-receipt-text-outline
							</v-icon>
						</div>
					</template>

					<template v-slot:no-data>
						<p>Еще нет пробитых чеков</p>
					</template>

					<template v-slot:bottom>
						<DataTablePagination 
							:itemsPerPage="itemsPerPage"
							:totalItems="totalItems"
							:page="page"
							@update:itemsPerPage="itemsPerPage = $event"
							@update:page="page = $event"
						/>
					</template>
				</v-data-table-server>
			</template>
		</CrudPage>

		<v-dialog
			v-model="detailsModal"
			width="auto"
			max-width="600"
			min-width="400"
			scrollable
			content-class="receipt-details-dialog"
		>
			<v-card v-if="selectedReceipt">
				<v-card-title class="d-flex justify-space-between align-center">
					<span class="d-flex align-center ga-2">
						<v-icon icon="mdi-receipt-text-outline" />
						Просмотр чека
					</span>
					<v-btn icon="mdi-close" variant="plain" @click="detailsModal = false"></v-btn>
				</v-card-title>

				<v-card-text>
					<ReceiptDetails :receipt="selectedReceipt" />
				</v-card-text>

				<v-card-actions class="flex-wrap">
					<v-btn
						v-if="selectedReceipt.status === 'wait'"
						color="primary"
						prepend-icon="mdi-reload"
						variant="outlined"
						@click="updateStatus(selectedReceipt.id)"
					>Обновить статус</v-btn>
					<v-btn
						v-if="canRefund(selectedReceipt)"
						color="danger"
						@click="openRefundModal(selectedReceipt)"
					>Оформить возврат</v-btn>
					<v-btn :href="route('receipts.pdf', selectedReceipt.id)">Скачать PDF</v-btn>
					<v-btn @click="detailsModal = false">Отмена</v-btn>
				</v-card-actions>
			</v-card>
		</v-dialog>

		<ReceiptPaymentsDialog v-model="paymentsModal" :payments="selectedPayments" />

		<v-dialog
			v-model="refundModal"
			width="auto"
			max-width="600"
			min-width="400"
			:persistent="refunding"
		>
			<v-card
				prepend-icon="mdi-receipt-text-outline"
			>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Пробить возврат
						<v-btn icon="mdi-close" variant="plain" :disabled="refunding" @click="refundModal = false"></v-btn>
					</div>
				</template>

				<v-card-text>
					Вы уверены, что хотите пробить возврат на сумму {{ selectedReceipt?.amount.toLocaleString() }}р?
				</v-card-text>

				<template v-slot:actions>
					<v-btn :loading="refunding" :disabled="refunding" @click="makeRefund">Да</v-btn>
					<v-btn :disabled="refunding" @click="refundModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
	</AppLayout>
</template>

<style>
	.v-table__wrapper {
		overflow-y: hidden;
	}

	.receipt-details-dialog {
		max-height: 90vh;
	}

	.receipt-details-dialog > .v-card {
		max-height: 90vh;
	}
</style>
