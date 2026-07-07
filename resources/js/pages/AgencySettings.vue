<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import { useForm } from "laravel-precognition-vue";
	import { useUserStore } from "@/stores/user";
	import { useToastsStore } from "@/stores/toasts";

	const userStore = useUserStore();
	const toastsStore = useToastsStore();

	const detailsForm = useForm("post", route("agency.update-details", userStore.activeAgency.id), {
		name: userStore.activeAgency.name,
		inn: userStore.activeAgency.inn,
	});

	const submitDetailsForm = () => detailsForm.submit()
		.then(response => {
			let updatedAgency = response.data;
			updatedAgency.pivot = userStore.activeAgency.pivot;

			userStore.setAgency(updatedAgency);

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
