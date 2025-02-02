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
				filters: [{column: 'is_draft', value: 1}],
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

	const deleting = ref(false);
	function openDeleteModal(item) {
		selectedReceipt.value = item
		deleting.value = true
	}
	function deleteReceipt() {
		axios.delete(route('receipts.destroy', selectedReceipt.value.id))
			.then(resp => {
				receipts.splice(receipts.indexOf(selectedReceipt.value), 1);
				deleting.value = false;
				selectedReceipt.value = null;
			})
			.catch(error => {
				toastsStore.handleResponseError(error);
			})
	}



	const insurers = reactive([]);
	const selectedInsurer = reactive({});

	function loadContracts() {
		const insurer = insurers.find(insurer => insurer.name === createForm.insurer_name);

		Object.assign(selectedInsurer, insurer);
		createForm.contract_name = null;
	}
	
	function loadInsurers() {
		axios.get(route('insurers.index', {agency_id: userStore.user.agency_id}))
			.then(response => {
				Object.assign(insurers, response.data);
			})
	}
	loadInsurers();
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Черновики чеков</H1>

				<div class="justify-start mt-3 d-flex">
					<v-text-field v-model="search" density="compact" placeholder="Поиск" variant="outlined" hide-details max-width="300px" 
						append-inner-icon="mdi-magnify"
						clearable
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
					item-value="name"
					@update:options="loadItems"
					density="comfortable"
				>
					<template v-slot:item.actions="{ item }">
						<v-icon
							class="me-2"
							size="small"
							@click="openDetailsModal(item)"
							color="primary"
							v-tooltip:bottom="'Подробнее'"
						>
							mdi-receipt-text-outline
						</v-icon>
						<RouterLink :to="{ name: 'receipts.edit', params: { id: item.id } }">
							<v-icon
								icon="mdi-pencil"
								variant="plain"
								size="small"
								v-tooltip:bottom="'Редактировать'"
							></v-icon>
						</RouterLink>
						<v-btn
							icon="mdi-trash-can-outline"
							variant="plain"
							size="small"
							color="error"
							v-tooltip:bottom="'Удалить'"
							@click="openDeleteModal(item)"
						>
						</v-btn>
					</template>

					<template v-slot:no-data>
						<p>Черновики чеков не найдены</p>
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

					<!-- <div class="flex-col mt-6 d-flex ga-3">
						<v-btn color="primary" prepend-icon="mdi-cash">Пробить за наличные</v-btn>
						<v-btn color="primary" prepend-icon="mdi-credit-card-outline">Пробить за безналичный расчет</v-btn>
					</div> -->
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="detailsModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
		
		<v-dialog
			v-model="deleting"
			width="auto"
			max-width="400"
		>
			<v-card
				prepend-icon="mdi-delete"
				text="Удалить чек?"
			>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Удаление
						<v-btn icon="mdi-close" variant="plain" @click="deleting = false"></v-btn>
					</div>
				</template>

				<template v-slot:actions>
					<v-btn @click="deleteReceipt" color="error">Удалить</v-btn>
					<v-btn @click="deleting = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
	</AppLayout>
</template>
