<script setup>
import Toasts from '@/components/Toasts.vue';
import { useUserStore } from '@/stores/user';
import { useRouter, useRoute } from 'vue-router';


const userStore = useUserStore();
const router = useRouter();
const route = useRoute();

function logout() {
	userStore.logout()

	router.push({name: 'login'});
}
</script>

<template>
    <v-app>
        <v-app-bar class="px-2">
			<span class="text-h4">Insurance</span>
			<v-spacer></v-spacer>
            <v-btn prepend-icon="mdi-logout" text="Выйти" @click="logout"></v-btn>
        </v-app-bar>

        <v-navigation-drawer>
            <v-list>
                <v-list-item>
                    <RouterLink :to="{ name: 'home' }" class="d-flex ga-1" :class="route.name === 'home' ? 'text-info' : ''">
						<v-icon icon="mdi-file-send-outline"></v-icon>
						Загрузка файлов
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
