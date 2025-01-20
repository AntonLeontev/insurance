<script setup>
import Toasts from '@/components/Toasts.vue';
import Profile from '@/components/Profile.vue';
</script>

<template>
    <v-app>
        <v-app-bar class="px-4">
			<span class="text-h4">Insurance</span>
			<v-spacer></v-spacer>
			<Profile />
        </v-app-bar>

        <v-navigation-drawer>
            <v-list>
                <v-list-item>
                    <RouterLink :to="{ name: 'home' }" class="d-flex ga-1" :class="$route.name === 'home' ? 'text-info' : ''">
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
