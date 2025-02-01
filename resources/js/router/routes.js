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
        path: "/receipts/drafts",
        component: () => import("@/pages/receipts/ReceiptsDrafts.vue"),
        name: "receipts.drafts",
        meta: { auth: true },
    },
    {
        path: "/receipts/submitted",
        component: () => import("@/pages/receipts/ReceiptsSubmitted.vue"),
        name: "receipts.submitted",
        meta: { auth: true },
    },
    {
        path: "/receipts/create",
        component: () => import("@/pages/receipts/ReceiptsCreate.vue"),
        name: "receipts.create",
        meta: { auth: true },
    },
    {
        path: "/receipts/:id/edit",
        component: () => import("@/pages/receipts/ReceiptsUpdate.vue"),
        name: "receipts.edit",
        meta: { auth: true },
    },
    {
        path: "/receipts/:id/details",
        component: () => import("@/pages/receipts/ReceiptsDetails.vue"),
        name: "receipts.details",
        meta: { auth: true },
    },
    {
        path: "/receipts/refund",
        component: () => import("@/pages/receipts/ReceiptsRefund.vue"),
        name: "receipts.refund",
        meta: { auth: true },
    },
    { path: "/login", component: Login, name: "login" },
    {
        path: "/forgot-password",
        component: () => import("@/pages/auth/ForgotPassword.vue"),
        name: "forgot-password",
    },
    {
        path: "/create-password/:email/:token",
        component: () => import("@/pages/auth/CreatePassword.vue"),
        name: "create-password",
    },
    {
        path: "/reset-password",
        component: () => import("@/pages/auth/ResetPassword.vue"),
        name: "reset-password",
    },
    {
        path: "/:pathMatch(.*)*",
        name: "404",
        component: () => import("@/pages/404.vue"),
    },
];
