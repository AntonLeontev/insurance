<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import DataTablePagination from '@/components/DataTablePagination.vue';
	import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
	import { reactive, ref, watch } from 'vue';
	import { useUserStore } from '@/stores/user';
	import { useToastsStore } from '@/stores/toasts';
	import axios from 'axios';

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const headers = [
        { title: 'ФИО', align: 'start', key: 'surname', value: item => `${item.surname} ${item.name} ${item.patronymic ?? ''}`, width: '20%' },
        { title: 'Договор', key: 'contract_series', value: item => `${item.contract_series} ${item.contract_number}`, align: 'start' },
        { title: 'Страховая', key: 'insurer_name', align: 'start' },
		{ title: 'Тип договора', key: 'contract_name', align: 'start' },
		{ title: 'Стоимость', key: 'amount', align: 'start' },
		{ title: 'Статус', key: 'status', align: 'start' },
		{ title: 'Действия', key: 'actions', align: 'end', sortable: false }
    ];
	const receipts = reactive([]);
	const loading = ref(false);

	const itemsPerPage = ref(localStorage.getItem('receipts-drafts:itemsPerPage') || 10);
	const totalItems = ref(0);
	const page = ref(1);
	const sortBy = ref(null);
	const search = ref(null);

	watch(itemsPerPage, () => {
		localStorage.setItem('receipts-drafts:itemsPerPage', itemsPerPage.value);
		loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value, search: search.value });
	});
	watch(page, () => {
		loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value, search: search.value });
	});
	watch(search, () => {
		loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value, search: search.value });
	});

	function loadItems({ page, itemsPerPage, sortBy, search }) {
		loading.value = true;

		axios.get(
			route('receipts.index'), 
			{ params: { 
				page, 
				items_per_page: itemsPerPage, 
				sort: sortBy,
				search: search,
				filters: [{column: 'is_draft', value: 0}],
			} }
		)
			.then(response => {
				receipts.splice(0, receipts.length, ...response.data.data);
				totalItems.value = response.data.total;
			})
			.finally(() => {
				loading.value = false;
			})
	}

	const selectedReceipt = ref(null);

	const detailsModal = ref(false);
	function openDetailsModal(item) {
		selectedReceipt.value = item
		detailsModal.value = true
	}

	const refundModal = ref(false);
	function openRefundModal(item) {
		selectedReceipt.value = item
		refundModal.value = true
	}
	function makeRefund() {
		axios.post(route('receipts.refund', selectedReceipt.value?.id))
			.then(response => loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value, search: search.value }))
			.catch(error => toastsStore.handleResponseError(error))
			.finally(() => refundModal.value = false)
	}

	const updatingStatus = ref(false);
	function updateStatus(id) {
		if (updatingStatus.value) {
			return;
		}

		updatingStatus.value = true;
		
		axios.get(route('receipts.get-status', id))
			.then(response => {
				let index = receipts.findIndex(receipt => receipt.id === id);
				receipts[index] = response.data;

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
			</template>

			<template v-slot:content>
				<v-data-table-server
					v-model:items-per-page="itemsPerPage"
					v-model:page="page"
					:headers="headers"
					:items="receipts"
					:items-length="totalItems"
					:loading="loading"
					item-value="name"
					@update:options="loadItems"
					density="comfortable"
				>
					<template v-slot:item.status="{ item }">
						<span class="" v-if="item.status === 'wait'">
							<v-icon icon="mdi-timer-sand" />
							В обработке
						</span>
						<span class="" v-if="item.status === 'done'">
							<v-icon icon="mdi-check-circle-outline" color="primary" />
							Успешно
						</span>
						<span class="" v-if="item.status === 'fail'">
							<v-icon icon="mdi-alert" color="danger" />
							Ошибка
							<v-icon icon="mdi-information-outline" color="danger" v-tooltip:top="item.error_text" />
						</span>
					</template>

					<template v-slot:item.actions="{ item }">
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
							v-if="item.status === 'done' && userStore.user.role !== 'cashier'"
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
		>
			<v-card
				prepend-icon="mdi-receipt-text-outline"
			>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Просмотр чека
						<v-btn icon="mdi-close" variant="plain" @click="detailsModal = false"></v-btn>
					</div>
				</template>

				<v-card-text>
					<ReceiptDetails :receipt="selectedReceipt" />

					<div class="flex-col mt-6 d-flex ga-3" v-if="selectedReceipt.status === 'wait'">
						<v-btn color="primary" prepend-icon="mdi-reload" variant="outline" @click="updateStatus(selectedReceipt.id)">Обновить статус</v-btn>
					</div>
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="detailsModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>

		<v-dialog
			v-model="refundModal"
			width="auto"
			max-width="600"
			min-width="400"
		>
			<v-card
				prepend-icon="mdi-receipt-text-outline"
			>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Пробить возврат
						<v-btn icon="mdi-close" variant="plain" @click="refundModal = false"></v-btn>
					</div>
				</template>

				<v-card-text>
					Вы уверены, что хотите пробить возврат на сумму {{ selectedReceipt?.amount.toLocaleString() }}р?
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="makeRefund">Да</v-btn>
					<v-btn @click="refundModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
	</AppLayout>
</template>
