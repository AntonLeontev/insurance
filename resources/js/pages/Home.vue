<script setup>
    import AppLayout from '@/layouts/AppLayout.vue';
	import CrudPage from '@/components/CrudPage.vue';

</script>

<template>
    <AppLayout>
		<CrudPage>
			<template v-slot:header>
			</template>
			<template v-slot:content>
			Home
			</template>
		</CrudPage>
    </AppLayout>
</template>
