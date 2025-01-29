<script setup>
	import AppLayout from '@/layouts/AppLayout.vue';
	import H1 from '@/components/H1.vue';
	import CrudPage from '@/components/CrudPage.vue';
</script>

<template>
	<AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Черновики чеков</H1>
			</template>
		</CrudPage>
	</AppLayout>
</template>
