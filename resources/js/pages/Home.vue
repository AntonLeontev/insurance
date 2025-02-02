<script setup>
    import AppLayout from '@/layouts/AppLayout.vue';
	import CrudPage from '@/components/CrudPage.vue';
	import H1 from '@/components/H1.vue';

</script>

<template>
    <AppLayout>
		<CrudPage>
			<template v-slot:header>
				<H1>Dashboard</H1>
			</template>
			<template v-slot:content>
			</template>
		</CrudPage>
    </AppLayout>
</template>
