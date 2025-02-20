<script setup>
import { useUserStore } from '@/stores/user';
import { useRouter, useRoute } from 'vue-router';

const userStore = useUserStore();
const router = useRouter();


function logout() {
	userStore.logout()

	router.push({name: 'login'});
}
</script>

<template>
	<div class="d-flex">
		<div class="px-3 d-flex align-center max-w-[150px] md:max-w-[300px]" :title="userStore.user.agency?.name">
			<span class="font-weight-black text-truncate">{{ userStore.user.agency?.name }}</span>
		</div>
		<div>
			<div class="mx-auto px-3 d-flex max-w-[150px] md:max-w-[300px] align-center ga-2 cursor-pointer">
				<div class="">
					<v-icon icon="mdi-account-circle" size="x-large" />
				</div>
				<div class="min-w-[50px]">
					<div class="font-weight-black text-truncate text-start" :title="userStore.user.name">{{ userStore.user.name }}</div>
					<div class="text-caption text-medium-emphasis text-truncate text-start" :title="userStore.user.email">{{ userStore.user.email }}</div>
					<div class="text-caption text-medium-emphasis text-truncate text-start">{{ userStore.user.role_name }}</div>
				</div>
			</div>

			<v-menu activator="parent">
				<v-list>
					<RouterLink :to="{ name: 'profile' }" class="transition d-flex ga-2 hover:bg-slate-200">
						<v-list-item>
							<v-icon icon="mdi-account-circle" color="green"></v-icon>
							Профиль
						</v-list-item>
					</RouterLink>
					<v-list-item class="transition hover:bg-slate-200" @click="logout">
						<v-list-item-title class="cursor-pointer d-flex ga-2">
							<v-icon icon="mdi-logout" color="green" />
							Выйти
						</v-list-item-title>
					</v-list-item>
				</v-list>
			</v-menu>
		</div>
	</div>



</template>
