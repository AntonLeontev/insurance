<script setup>
import AppLayout from "@/layouts/AppLayout.vue";
import H1 from "@/components/H1.vue";
import { useUserStore } from "@/stores/user";

const userStore = useUserStore();
</script>

<template>
    <AppLayout>
        <div class="w-100">
            <H1>Выбор агентства</H1>

            <div class="flex justify-center">
                <v-card
                    class="mx-auto"
                    max-width="700"
                    min-width="300"
                    variant="flat"
                >
                    <v-list density="default" variant="flat">
                        <v-list-subheader
                            >Выберите агентство для работы</v-list-subheader
                        >

                        <v-list-item
                            v-for="agency in userStore.user?.agencies"
                            :active="userStore.activeAgency?.id === agency.id"
                            color="primary"
                            @click="userStore.setAgency(agency)"
                        >
                            <v-list-item-title>
                                {{ agency.name }}
                                <span v-if="agency.inn">
                                    (ИНН {{ agency.inn }})
                                </span>
                            </v-list-item-title>
                            <v-list-item-subtitle>{{
                                agency.pivot.role_translated
                            }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                </v-card>
            </div>
        </div>
    </AppLayout>
</template>
