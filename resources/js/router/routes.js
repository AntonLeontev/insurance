import Login from "@/pages/auth/Login.vue";

export default [
    {
        path: "/",
        component: () => import("@/pages/Home.vue"),
        name: "home",
        meta: { auth: true, title: "Главная" },
    },
    {
        path: "/profile",
        component: () => import("@/pages/Profile.vue"),
        name: "profile",
        meta: { auth: true, title: "Профиль" },
    },
    {
        path: "/users",
        component: () => import("@/pages/Users.vue"),
        name: "users",
        meta: { auth: true, title: "Пользователи" },
    },
    {
        path: "/agency-settings",
        component: () => import("@/pages/AgencySettings.vue"),
        name: "agency-settings",
        meta: { auth: true, title: "Настройки агенства" },
    },
    {
        path: "/atol-settings",
        component: () => import("@/pages/AtolSettings.vue"),
        name: "atol-settings",
        meta: { auth: true, title: "Настройки Атол" },
    },
    {
        path: "/tbank-settings",
        component: () => import("@/pages/TbankSettings.vue"),
        name: "tbank-settings",
        meta: { auth: true, title: "Настройки Тбанка" },
    },
    {
        path: "/products",
        component: () => import("@/pages/Products.vue"),
        name: "products",
        meta: { auth: true, title: "Страховые продукты" },
    },
    {
        path: "/receipts/drafts",
        component: () => import("@/pages/receipts/ReceiptsDrafts.vue"),
        name: "receipts.drafts",
        meta: { auth: true, title: "Черновики чеков" },
    },
    {
        path: "/receipts/submitted",
        component: () => import("@/pages/receipts/ReceiptsSubmitted.vue"),
        name: "receipts.submitted",
        meta: { auth: true, title: "Оформленные чеки" },
    },
    {
        path: "/receipts/create",
        component: () => import("@/pages/receipts/ReceiptsCreate.vue"),
        name: "receipts.create",
        meta: { auth: true, title: "Новый чек" },
    },
    {
        path: "/receipts/:id/edit",
        component: () => import("@/pages/receipts/ReceiptsUpdate.vue"),
        name: "receipts.edit",
        meta: { auth: true, title: "Редактировать чек" },
    },
    {
        path: "/receipts/:id/details",
        component: () => import("@/pages/receipts/ReceiptsDetails.vue"),
        name: "receipts.details",
        meta: { auth: true, title: "Просмотр чека" },
    },
    {
        path: "/receipts/refund",
        component: () => import("@/pages/receipts/ReceiptsRefund.vue"),
        name: "receipts.refund",
        meta: { auth: true, title: "Возврат" },
    },
    {
        path: "/receipts/:id/checkout",
        component: () => import("@/pages/receipts/ReceiptCheckout.vue"),
        name: "receipts.checkout-page",
        meta: { title: "Оплата договора страхования" },
    },
    {
        path: "/receipts/:id/payment-success",
        component: () => import("@/pages/receipts/PaymentSuccess.vue"),
        name: "receipts.payment-success",
        meta: { title: "Оплата успешна" },
    },
    {
        path: "/agencies",
        component: () => import("@/pages/Agencies.vue"),
        name: "agencies",
        meta: { auth: true, title: "Выбор агентства" },
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
