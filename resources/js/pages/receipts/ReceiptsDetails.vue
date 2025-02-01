<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import H1 from '@/components/H1.vue';
import CrudPage from '@/components/CrudPage.vue';
import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
import { reactive, ref } from 'vue';
import { useUserStore } from "@/stores/user";
import { useToastsStore } from "@/stores/toasts";
import { useRoute } from 'vue-router';
import axios from 'axios';

const userStore = useUserStore();
const toastsStore = useToastsStore();
const curRoute = useRoute();

const receipt = reactive({});

function loadReceipt() {
	axios.get(route('receipts.show', {id: curRoute.params.id}))
		.then(response => {
			Object.assign(receipt, response.data);
		})
		.catch(error => toastsStore.handleResponseError(error));
}


loadReceipt();
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Просмотр чека</H1>
			</template>

			<template v-slot:content>
				<div class="justify-center d-flex">
					<ReceiptDetails :receipt="receipt" width="600px" />
				</div>
			</template>
		</CrudPage>
	</AppLayout>
</template>
