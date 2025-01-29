<template>
	<v-empty-state
		headline="404"
		title="Страница не найдена"
		image="https://vuetifyjs.b-cdn.net/docs/images/logos/v.png"
	>
		<RouterLink :to="{ name: 'home' }">
			<v-btn prepend-icon="mdi-home" color="primary">На главную</v-btn>
		</RouterLink>
	</v-empty-state>
</template>

<script setup>
	import { RouterLink } from 'vue-router';
</script>
