<script setup>
	import { defineProps, computed } from 'vue';


	const props = defineProps({
		totalItems: { required: true, default: 0 },
		itemsPerPage: { required: true, default: 10 },
		page: { type: Number, required: true, default: 1 },
	})

	const lastPage = computed(() => {
		return Math.ceil(props.totalItems / props.itemsPerPage);
	})
</script>

<template>
	<div class="justify-between d-flex align-center">
		<div class=""></div>
		
		<div class="d-flex align-center ga-4">
			<span>Показывать по</span>

			<v-select
				class="w-28 per-page-select"
				:items="[10, 25, 50, 100]"
				:value="itemsPerPage"
				title="Показывать на странице"
				variant="outlined"
				density="compact"
				@update:modelValue="$emit('update:itemsPerPage', $event)"
				hide-details
			>
			</v-select>

			<span>
				{{ itemsPerPage * (page - 1) + 1}} - {{ Math.min(itemsPerPage * page, totalItems) }} из {{ totalItems }}
			</span>

			<div class="d-flex">
				<v-btn icon="mdi-page-first" variant="plain" :disabled="page === 1" @click="$emit('update:page', 1)"></v-btn>
				<v-btn icon="mdi-chevron-left" variant="plain" :disabled="page === 1" @click="$emit('update:page', page - 1)"></v-btn>
				<v-btn icon="mdi-chevron-right" variant="plain" :disabled="page === lastPage" @click="$emit('update:page', page + 1)"></v-btn>
				<v-btn icon="mdi-page-last" variant="plain" :disabled="page === lastPage" @click="$emit('update:page', lastPage)"></v-btn>
			</div>
		</div>
	</div>
</template>
