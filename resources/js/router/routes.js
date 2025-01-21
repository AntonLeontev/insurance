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
    {
        path: "/users",
        component: () => import("@/pages/Users.vue"),
        name: "users",
        meta: { auth: true },
    },
    {
        path: "/agency-settings",
        component: () => import("@/pages/AgencySettings.vue"),
        name: "agency-settings",
        meta: { auth: true },
    },
    {
        path: "/atol-settings",
        component: () => import("@/pages/AtolSettings.vue"),
        name: "atol-settings",
        meta: { auth: true },
    },
    {
        path: "/products",
        component: () => import("@/pages/Products.vue"),
        name: "products",
        meta: { auth: true },
    },
    {
        path: "/checks",
        component: () => import("@/pages/Checks.vue"),
        name: "checks",
        meta: { auth: true },
    },
    { path: "/login", component: Login, name: "login" },
    {
        path: "/forgot-password",
        component: () => import("@/pages/auth/ForgotPassword.vue"),
        name: "forgot-password",
    },
    {
        path: "/reset-password",
        component: () => import("@/pages/auth/ResetPassword.vue"),
        name: "reset-password",
    },
];
