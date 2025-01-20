<script setup lang="ts">
import Toasts from '@/components/Toasts.vue';
</script>

<template>
	<v-app>
		<v-main class="justify-center d-flex align-center">
			<div class="w-100">
				<div class="text-center text-h3">
					Insurance
				</div>
				<slot></slot>
			</div>
		</v-main>
	</v-app>

	<Toasts />
</template>
