import { createWebHistory, createRouter } from "vue-router";
import routes from "./routes";
import { useUserStore } from "@/stores/user";

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from) => {
    const userStore = useUserStore();

    if (
        to.meta.auth &&
        (userStore.user === null || userStore.activeAgency === null) &&
        to.name !== "receipts.details"
    ) {
        await userStore.getUser();

        if (userStore.user === null) {
            return { name: "login" };
        }

        if (userStore.activeAgency === null && to.name !== "agencies") {
            return { name: "agencies" };
        }
    }

	document.title = to.meta.title
        ? to.meta.title + " | Fiscal Hub"
        : "Fiscal Hub";
});

export default router;
