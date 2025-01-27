<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import DataTablePagination from '@/components/DataTablePagination.vue';
	import { ref, reactive, watch } from 'vue';
	import { useForm } from "laravel-precognition-vue";
	import axios from 'axios';

	import {useUserStore} from '@/stores/user';
	const userStore = useUserStore();

	import {useToastsStore} from '@/stores/toasts';
	const toastsStore = useToastsStore();

    const headers = [
        { title: 'Имя', align: 'start', key: 'name', width: '30%' },
        { title: 'Email', key: 'email', align: 'start', width: '30%' },
        { title: 'Роль', key: 'role', align: 'start' },
		{ title: 'Действия', key: 'actions', sortable: false, align: 'end' }
    ];
	const serverItems = reactive([]);
	const loading = ref(false);

	const itemsPerPage = ref(localStorage.getItem('users:itemsPerPage') || 10);
	const totalItems = ref(0);
	const page = ref(1);
	const sortBy = ref(null);

	watch(itemsPerPage, () => {
		localStorage.setItem('users:itemsPerPage', itemsPerPage.value);
		loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value });
	});
	watch(page, () => {
		loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value });
	});

	function loadItems({ page, itemsPerPage, sortBy }) {
		loading.value = true;

		axios.get(
			route('agencies.users', userStore.user.agency_id), 
			{ params: { 
				page, 
				items_per_page: itemsPerPage, 
				sort: sortBy,
			} }
		)
			.then(response => {
				serverItems.splice(0, serverItems.length, ...response.data.data);
				totalItems.value = response.data.total;
			})
			.finally(() => {
				loading.value = false;
			})
	}

	const roles = [
		// { label: 'Администратор', value: 'admin' },
		{ label: 'Кассир', value: 'cashier' },
		{ label: 'Старший кассир', value: 'senior cashier' },
	];

	const creating = ref(false);
	const deleting = ref(false);
	const deletingItem = ref(null);

	function openCreateModal(item) {
		creating.value = true
	}

	function openDeleteModal(item) {
		deleting.value = true
		deletingItem.value = item
	}


	function deleteUser() {
		axios.delete(route('agencies.users.destroy', {
			agency: userStore.user.agency_id,
			id: deletingItem.value.id
		}))
			.then(response => {
				loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value })
			})
			.finally(() => {
				deleting.value = false
			})
	}

	function closeCreateForm() {
		creating.value = false
		createForm.reset()
		createForm.errors = {}
	}

	const createForm = useForm("post", route("agencies.users.create", userStore.user.agency_id), {
		email: '',
		name: '',
		role: null,
	});

	const submitCreateForm = () => createForm.submit()
		.then(response => {
			loadItems({ page: page.value, itemsPerPage: itemsPerPage.value, sortBy: sortBy.value })
			closeCreateForm();
		})
		.catch(error => {
			toastsStore.handleResponseError(error);
		});

</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<div class="justify-between d-flex">
					<H1>Пользователи</H1>
					<v-btn prepend-icon="mdi-plus" @click="openCreateModal" color="primary">Пригласить пользователя</v-btn>
				</div>
			</template>
			<template v-slot:content>

				<v-data-table-server
					v-model:items-per-page="itemsPerPage"
					v-model:page="page"
					:headers="headers"
					:items="serverItems"
					:items-length="totalItems"
					:loading="loading"
					item-value="name"
					@update:options="loadItems"
					density="comfortable"
				>
					<template v-slot:item.actions="{ item }">
						<!-- <v-icon
							class="me-2"
							size="small"
							@click="editItem(item)"
						>
							mdi-pencil
						</v-icon> -->
						<v-btn
							icon="mdi-trash-can-outline"
							variant="plain"
							size="small"
							color="error"
							title="Удалить пользователя"
							@click="openDeleteModal(item, $event)"
							v-if="userStore.user.id !== item.id"
						>
							
						</v-btn>
					</template>

					<template v-slot:no-data>
						<v-btn
							color="primary"
						>
							Пользователи еще не созданы
						</v-btn>
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

				<!-- ------ -->
				<!-- Modals -->
				<!-- ------ -->
				
				<v-dialog
					v-model="creating"
					width="auto"
					max-width="400"
				>
					<v-card
						prepend-icon="mdi-plus"
					>
						<template v-slot:title>
							<div class="justify-between d-flex align-center">
								Приглашение пользователя
								<v-btn icon="mdi-close" variant="plain" @click="closeCreateForm"></v-btn>
							</div>
						</template>
						
						<template v-slot:text>
							<form class="flex-col d-flex ga-3">
								<v-text-field
									label="Email"
									variant="outlined"
									v-model="createForm.email"
									:hint="createForm.errors.email"
									persistent-hint
									:class="createForm.invalid('email') ? 'text-danger' : ''"
								></v-text-field>
								<v-text-field
									label="Имя или название организации"
									variant="outlined"
									v-model="createForm.name"
									:hint="createForm.errors.name"
									persistent-hint
									:class="createForm.invalid('name') ? 'text-danger' : ''"
								></v-text-field>
								<v-select
									label="Роль"
									variant="outlined"
									v-model="createForm.role"
									:hint="createForm.errors.role"
									persistent-hint
									:class="createForm.invalid('role') ? 'text-danger' : ''"
									:items="roles"
									item-title="label"
									item-value="value"
								></v-select>
							</form>
						</template>

						<template v-slot:actions>
							<v-btn @click="submitCreateForm" color="primary" :disabled="createForm.processing">Пригласить</v-btn>
							<v-btn @click="closeCreateForm">Отмена</v-btn>
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
						:text="'Удалить пользователя ' + deletingItem.name + '?'"
					>
						<template v-slot:title>
							<div class="justify-between d-flex align-center">
								Удаление
								<v-btn icon="mdi-close" variant="plain" @click="deleting = false"></v-btn>
							</div>
						</template>

						<template v-slot:actions>
							<v-btn @click="deleteUser" color="error">Удалить</v-btn>
							<v-btn @click="deleting = false">Отмена</v-btn>
						</template>
					</v-card>
				</v-dialog>
			</template>
		</CrudPage>
	</AppLayout>
</template>
