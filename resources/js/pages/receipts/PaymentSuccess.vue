<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

// const route = useRoute();
// const router = useRouter();

// const loading = ref(true);
// const receipt = ref(null);
// const error = ref(null);

// function loadData() {
// 	loading.value = true;
	
// 	// Просто загружаем страницу - backend обработает платеж и вернет view
// 	// После загрузки можно попробовать получить данные чека для отображения
// 	axios.get(route('receipts.checkout-data', { receipt: route.params.id }))
// 		.then(response => {
// 			receipt.value = response.data;
// 		})
// 		.catch(err => {
// 			if (err.response?.status === 404) {
// 				router.push({ name: '404' });
// 			} else {
// 				error.value = 'Не удалось загрузить данные чека';
// 			}
// 		})
// 		.finally(() => {
// 			loading.value = false;
// 		});
// }

// onMounted(() => {
// 	loadData();
// });
</script>

<template>
	<div class="justify-center d-flex align-center" style="min-height: 100vh; padding: 20px;">
		<v-card class="pa-6" max-width="600" width="100%">
			<v-card-title class="mb-4 text-h5">
				<div class="justify-center d-flex align-center">
					<v-icon icon="mdi-check-circle" color="success" class="mr-2"></v-icon>
					Оплата успешно завершена
				</div>
				<div class="justify-center mt-3 text-lg d-flex align-center">
					Эту страницу можно закрыть
				</div>
			</v-card-title>

			<!-- <v-card-text v-if="loading">
				<v-progress-circular indeterminate color="primary"></v-progress-circular>
				<span class="ml-4">Обработка платежа...</span>
			</v-card-text>

			<v-card-text v-else-if="error">
				<v-alert type="error">{{ error }}</v-alert>
			</v-card-text> -->

			<!-- <v-card-text v-else>
				<v-alert type="success" class="mb-4">
					Платеж успешно обработан. Чек отправлен на фискализацию.
				</v-alert>

				<div v-if="receipt" class="mt-4">
					<p><strong>Номер чека:</strong> {{ receipt.id }}</p>
					<p><strong>Сумма:</strong> {{ (+receipt.amount).toLocaleString('ru-RU') }} ₽</p>
					<p v-if="receipt.status === 'done'"><strong>Статус фискализации:</strong> Успешно</p>
					<p v-else-if="receipt.status === 'wait'"><strong>Статус фискализации:</strong> В обработке</p>
					<p v-else-if="receipt.status === 'fail'"><strong>Статус фискализации:</strong> Ошибка</p>
				</div>

				<div class="mt-6 text-center">
					<v-btn color="primary" @click="router.push({ name: 'home' })">
						Вернуться на главную
					</v-btn>
				</div>
			</v-card-text> -->
		</v-card>
	</div>
</template>
