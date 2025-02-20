import { createWebHistory, createRouter } from "vue-router";
import routes from "./routes";
import { useUserStore } from "@/stores/user";

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from) => {
    const userStore = useUserStore();

    if (to.meta.auth && userStore.user === null) {
        await userStore.getUser();

        if (userStore.user === null) {
            return { name: "login" };
        }
    }

	document.title = to.meta.title
        ? to.meta.title + " | Fiscal Hub"
        : "Fiscal Hub";
});

export default router;
