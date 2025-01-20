<script setup>
	import { useToastsStore } from "@/stores/toasts"

	const store = useToastsStore();
</script>


<template>
<div class="fixed bottom-0 right-0 flex pb-4 pe-4 flex-column ga-2">
	<v-alert
		class="toast"
		v-for="toast in store.toasts"
		:key="toast.id"
		:type="toast.type"
		density="comfortable"
		variant="tonal"
  	>
		<div class="toast__content">
			<span>{{ toast.text }}</span>
			<v-btn icon="mdi-close" variant="text" density="compact" @click="store.remove(toast.id)"></v-btn>
		</div>
	</v-alert>
</div>
</template>

<style scoped>
.toast {
	width: 300px;
}

.toast__content	{
	display: flex;
	gap: 5px;
	align-items: start;
	justify-content: space-between;

}
</style>
