<script setup>
import { useUserStore } from '@/stores/user';
import { useRouter } from 'vue-router';

const userStore = useUserStore();
const router = useRouter();

function logout() {
	userStore.logout()

	router.push({name: 'login'});
}
</script>

<template>
	<div class="h-full d-flex">
		<div class="px-3 d-flex align-center justify-start ga-2 min-w-[150px] max-w-[150px] md:max-w-[300px] cursor-pointer transition hover:bg-slate-200" 
			:title="`${userStore.activeAgency?.name}\nНажмите, чтобы сменить агентство`"
			@click="router.push({name: 'agencies'})"	
			v-if="userStore.activeAgency?.name"
		>
			<div class="">
				<v-icon icon="mdi-domain" size="x-large" />
			</div>
			<div class="">
				<div class="font-weight-black text-truncate">{{ userStore.activeAgency?.name }}</div>
				<div class="text-caption text-medium-emphasis text-truncate text-start">{{ userStore.activeAgency?.pivot?.role_translated }}</div>
			</div>
		</div>
		<div class="h-full">
			<div class="mx-auto h-full px-3 d-flex max-w-[150px] md:max-w-[300px] align-center justify-center ga-2 cursor-pointer transition hover:bg-slate-200">
				<div class="">
					<v-icon icon="mdi-account-circle" size="x-large" />
				</div>
				<div class="min-w-[50px]">
					<div class="font-weight-black text-truncate text-start" :title="userStore.user?.name">{{ userStore.user?.name }}</div>
					<div class="text-caption text-medium-emphasis text-truncate text-start" :title="userStore.user?.email">{{ userStore.user?.email }}</div>
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
