import ForgotPassword from "@/pages/auth/ForgotPassword.vue";
import ResetPassword from "@/pages/auth/ResetPassword.vue";
import Login from "@/pages/auth/Login.vue";

export default [
    {
        path: "/",
        component: () => import("@/pages/Home.vue"),
        name: "home",
        meta: { auth: true },
    },
    {
        path: "/profile",
        component: () => import("@/pages/Profile.vue"),
        name: "profile",
        meta: { auth: true },
    },
    { path: "/login", component: Login, name: "login" },
    {
        path: "/forgot-password",
        component: ForgotPassword,
        name: "forgot-password",
    },
    {
        path: "/reset-password",
        component: ResetPassword,
        name: "reset-password",
    },
];
