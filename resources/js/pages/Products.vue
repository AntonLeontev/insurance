<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import { reactive, ref } from 'vue';
	import axios from 'axios';
	import { useForm } from 'laravel-precognition-vue';
	import { useUserStore } from '@/stores/user';
	import { useToastsStore } from '@/stores/toasts';

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const insurers = reactive([]);

	loadInsurers();

	function loadInsurers() {
		axios.get(route('insurers.index', {agency_id: userStore.user.agency_id}))
			.then(response => {
				Object.assign(insurers, response.data);
			})
	}

	const addInsurerModal = ref(false);
	const saveInsurerForm = useForm('post', route('insurers.store'), {
		agency_id: userStore.user.agency_id,
		name: '',
		inn: '',
	});
	function openAddInsurerModal() {
		addInsurerModal.value = true;
	}
	function saveInsurer() {
		saveInsurerForm.submit()
			.then(response => {
				loadInsurers();
				addInsurerModal.value = false;
				saveInsurerForm.reset();
			})
			.catch(error => {
				toastsStore.handleResponseError(error);
			})
	}
	function closeAddInsurerModal() {
		addInsurerModal.value = false;
	}


	const editInsurerModal = ref(false);
	const editInsurerForm = useForm('post', route('insurers.update'), {
		id: null,
		name: null,
		inn: null,
	});
	function openEditInsurerModal(item) {
		editInsurerForm.id = item.id
		editInsurerForm.name = item.name
		editInsurerForm.inn = item.inn
		editInsurerModal.value = true;
	}
	function editInsurer() {
		editInsurerForm.submit()
			.then(response => {
				let insurer = insurers.find(insurer => insurer.id === editInsurerForm.id)
				insurer.name = editInsurerForm.name;
				insurer.inn = editInsurerForm.inn;
				
				editInsurerModal.value = false;
				editInsurerForm.reset();
			})
			.catch(error => {
				toastsStore.handleResponseError(error);
			})
	}
	function closeEditInsurerModal() {
		editInsurerModal.value = false;
	}

	const deleteInsurerModal = ref(false);
	const deletableInsurer = ref(null);
	function openDeleteInsurerModal(item) {
		deletableInsurer.value = item
		deleteInsurerModal.value = true;
	}
	function deleteInsurer() {
		axios.delete(route('insurers.destroy', deletableInsurer.value.id))
			.then(response => {
				insurers.splice(insurers.indexOf(deletableInsurer.value), 1);
				deleteInsurerModal.value = false;
			})
			.catch(error => {
				toastsStore.handleResponseError(error);
			})
	}

</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<div class="justify-between d-flex">
					<H1>Страховые продукты</H1>

					<div class="d-flex ga-2">
						<v-btn prepend-icon="mdi-bank-plus" @click="openAddInsurerModal" color="primary">Добавить страховую компанию</v-btn>
					</div>
				</div>
			</template>

			<template v-slot:content>
				<div class="w-100">
					<v-expansion-panels>
						<v-expansion-panel
							v-for="insurer in insurers"
							:key="insurers.id"
						>
							<v-expansion-panel-title v-slot="{ expanded }">
								<div class="justify-between d-flex w-100">
									<div>{{ insurer.name }} - [ИНН {{ insurer.inn }}]</div>
									<v-fade-transition hide-on-leave>
										<div class="d-flex ga-3 me-5" v-if="expanded">
											<v-icon icon="mdi-pencil" :title="`Редактировать ${insurer.name}`" 
												@click="openEditInsurerModal(insurer)"
											></v-icon>
											<v-icon icon="mdi-delete" color="danger" :title="`Удалить ${insurer.name}`"
												@click="openDeleteInsurerModal(insurer)"
											></v-icon>
										</div>
									</v-fade-transition>
								</div>
							</v-expansion-panel-title>

							<v-expansion-panel-text>
								contracts
							</v-expansion-panel-text>
						</v-expansion-panel>
					</v-expansion-panels>

					<v-empty-state
						v-if="insurers.length === 0"
						icon="mdi-bank-off-outline"
						title="Страховые компании не найдены"
					>
						<template v-slot:text>
							<v-btn prepend-icon="mdi-bank-plus" @click="openAddInsurerModal" color="primary">Добавить страховую компанию</v-btn>
						</template>
					</v-empty-state>
				</div>
			</template>
		</CrudPage>

		<v-dialog
			v-model="addInsurerModal"
			width="auto"
			min-width="400"
			max-width="800"
		>
			<v-card>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Добавить страховую компанию
						<v-btn icon="mdi-close" variant="plain" @click="closeAddInsurerModal"></v-btn>
					</div>
				</template>

				<v-card-text>
					<form class="flex-col d-flex ga-2" @keyup.enter="saveInsurer">
						<v-text-field v-model="saveInsurerForm.name" label="Название" variant="outlined" persistant-hint
							:error="saveInsurerForm.invalid('name')" :error-messages="saveInsurerForm.errors.name"
						></v-text-field>
						<v-text-field v-model="saveInsurerForm.inn" label="ИНН" variant="outlined" persistant-hint
							:error="saveInsurerForm.invalid('inn')" :error-messages="saveInsurerForm.errors.inn"
						></v-text-field>
					</form>
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="saveInsurer" color="primary">Сохранить</v-btn>
					<v-btn @click="closeAddInsurerModal">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>

		<v-dialog
			v-model="editInsurerModal"
			width="auto"
			min-width="400"
			max-width="800"
		>
			<v-card>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Редактировать
						<v-btn icon="mdi-close" variant="plain" @click="closeEditInsurerModal"></v-btn>
					</div>
				</template>

				<v-card-text>
					<form class="flex-col d-flex ga-2" @keyup.enter="editInsurer">
						<v-text-field v-model="editInsurerForm.name" label="Название" variant="outlined" persistant-hint
							:error="editInsurerForm.invalid('name')" :error-messages="editInsurerForm.errors.name"
						></v-text-field>
						<v-text-field v-model="editInsurerForm.inn" label="ИНН" variant="outlined" persistant-hint
							:error="editInsurerForm.invalid('inn')" :error-messages="editInsurerForm.errors.inn"
						></v-text-field>
					</form>
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="editInsurer" color="primary">Сохранить</v-btn>
					<v-btn @click="closeEditInsurerModal">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>

		<v-dialog
			v-model="deleteInsurerModal"
			width="auto"
			min-width="400"
			max-width="800"
		>
			<v-card>
				<template v-slot:title>
					<div class="justify-between d-flex align-center">
						Удаление
						<v-btn icon="mdi-close" variant="plain" @click="deleteInsurerModal = false"></v-btn>
					</div>
				</template>

				<v-card-text>
					Удалить {{ deletableInsurer.name }} вместе со списком договоров? Все чеки останутся без изменений.
				</v-card-text>

				<template v-slot:actions>
					<v-btn @click="deleteInsurer" color="danger">Удалить</v-btn>
					<v-btn @click="deleteInsurerModal = false">Отмена</v-btn>
				</template>
			</v-card>
		</v-dialog>
	</AppLayout>
</template>
