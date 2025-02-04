<script setup>
import ReceiptDetails from '@/components/receipts/ReceiptDetails.vue';
import axios from 'axios';
import { defineProps, ref, defineEmits } from 'vue';
import { useToastsStore } from '@/stores/toasts';

const toastsStore = useToastsStore();

const props = defineProps({
	receipt: { required: true, type: Object },
	show: { required: true, type: Boolean },
})

const emit = defineEmits(['submitted'])

const submitting = ref(false);

function submit() {
	if (submitting.value) {
		return;
	}

	submitting.value = true;

	axios.post(route('receipts.submit'), props.receipt)
		.then(response => {
			emit('submitted');
		})
		.catch(error => toastsStore.handleResponseError(error))
		.finally(() => submitting.value = false)
}
</script>

<template>
    <v-dialog
		v-model="props.show"
		transition="dialog-bottom-transition"
		fullscreen
    >
		<v-card>
			<v-toolbar>
				<v-toolbar-title>Оформление чека</v-toolbar-title>

				<v-spacer></v-spacer>

				<v-toolbar-items>
					<v-btn
						icon="mdi-close"
						@click="$emit('close')"
					></v-btn>
				</v-toolbar-items>
			</v-toolbar>

			<div class="justify-center mt-4 d-flex">
				<div class="">
					<ReceiptDetails :receipt="props.receipt" />

					<div class="flex-col mt-6 d-flex ga-2">
						<v-btn color="primary" block v-if="props.receipt.payment_type === 'cash'" :loading="submitting" @click="submit">
							<span class="normal-case">Cформировать чек с</span>
							<span class="mx-1 font-bold uppercase">Наличной</span>
							<span class="lowercase">оплатой</span>
						</v-btn>
						<v-btn color="primary" block v-if="props.receipt.payment_type === 'cashless'" :loading="submitting" @click="submit">
							<span class="normal-case">Cформировать чек с</span>
							<span class="mx-1 font-bold uppercase">безНаличной</span>
							<span class="lowercase">оплатой</span>
						</v-btn>
						<v-btn color="danger" block @click="$emit('close')">Назад</v-btn>
					</div>
				</div>
			</div>
		</v-card>
    </v-dialog>
</template>
