<script setup>
import Toasts from '@/components/Toasts.vue';
import Profile from '@/components/Profile.vue';
import { useUserStore } from '@/stores/user';
import { ref } from 'vue';

const userStore = useUserStore();
const drawer = ref(window.innerWidth >= 1280);

</script>

<template>
    <v-app>
        <v-app-bar class="px-2 md:px-4">
			<v-app-bar-nav-icon variant="text" @click.stop="drawer = !drawer"></v-app-bar-nav-icon>
			<span class="text-md md:text-3xl">Fiscal-Hub</span>
			<v-spacer></v-spacer>
			<Profile />
        </v-app-bar>

		<v-navigation-drawer v-model="drawer" location="left">
			<v-list>
				<v-list-item>
					<RouterLink :to="{ name: 'receipts.create' }" class="d-flex ga-1" :class="$route.name === 'receipts.create' ? 'text-primary' : ''">
						<v-icon icon="mdi-receipt-text-plus"></v-icon>
						Новый чек
					</RouterLink>
				</v-list-item>
				<v-list-item>
					<RouterLink :to="{ name: 'home' }" class="d-flex ga-1" :class="$route.name === 'home' ? 'text-primary' : ''">
						<v-icon icon="mdi-home-circle-outline"></v-icon>
						Главная
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.activeAgency?.pivot?.role === 'admin'">
					<RouterLink :to="{ name: 'products' }" class="d-flex ga-1" :class="$route.name === 'products' ? 'text-primary' : ''">
						<v-icon icon="mdi-umbrella-outline"></v-icon>
						Страховые продукты
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.activeAgency?.pivot?.role === 'admin' || userStore.activeAgency?.pivot?.role === 'senior cashier'">
					<RouterLink :to="{ name: 'receipts.refund' }" class="d-flex ga-1" :class="$route.name === 'receipts.refund' ? 'text-primary' : ''">
						<v-icon icon="mdi-arrow-u-up-left-bold"></v-icon>
						Возврат чека
					</RouterLink>
				</v-list-item>
				<v-list-item>
					<RouterLink :to="{ name: 'receipts.drafts' }" class="d-flex ga-1" :class="$route.name === 'receipts.drafts' ? 'text-primary' : ''">
						<v-icon icon="mdi-receipt-clock-outline"></v-icon>
						Черновики чеков
					</RouterLink>
				</v-list-item>
				<v-list-item>
					<RouterLink :to="{ name: 'receipts.submitted' }" class="d-flex ga-1" :class="$route.name === 'receipts.submitted' ? 'text-primary' : ''">
						<v-icon icon="mdi-receipt-text-check"></v-icon>
						Оформленные чеки
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.activeAgency?.pivot?.role === 'admin'">
					<RouterLink :to="{ name: 'agency-settings' }" class="d-flex ga-1" :class="$route.name === 'agency-settings' ? 'text-primary' : ''">
						<v-icon icon="mdi-cog"></v-icon>
						Настройки агентства
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.activeAgency?.pivot?.role === 'admin'">
					<RouterLink :to="{ name: 'atol-settings' }" class="d-flex ga-1" :class="$route.name === 'atol-settings' ? 'text-primary' : ''">
						<v-icon icon="mdi-cash-register"></v-icon>
						Настройки АТОЛ
					</RouterLink>
				</v-list-item>
				<v-list-item v-if="userStore.activeAgency?.pivot?.role === 'admin'">
					<RouterLink :to="{ name: 'users' }" class="d-flex ga-1" :class="$route.name === 'users' ? 'text-primary' : ''">
						<v-icon icon="mdi-account-group"></v-icon>
						Пользователи
					</RouterLink>
				</v-list-item>
			</v-list>
		</v-navigation-drawer>

        <v-main>
			<v-container class="justify-center d-flex">
				<slot></slot>
			</v-container>
        </v-main>
    </v-app>

	<Toasts />
</template>
