import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

import LoginView from "../views/LoginView.vue";
import DashboardView from "../views/DashboardView.vue";
import ProductsView from "../views/ProductsView.vue";
import InventoryView from "../views/InventoryView.vue";
import OrdersView from "../views/OrdersView.vue";
import UsersView from "../views/admin/UsersView.vue";
import BranchesView from "../views/admin/BranchesView.vue";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: "/login", name: "login", component: LoginView },
        {
            path: "/",
            name: "dashboard",
            component: DashboardView,
            meta: { requiresAuth: true },
        },
        {
            path: "/products",
            name: "products",
            component: ProductsView,
            meta: { requiresAuth: true },
        },
        {
            path: "/inventory",
            name: "inventory",
            component: InventoryView,
            meta: { requiresAuth: true },
        },
        {
            path: "/orders",
            name: "orders",
            component: OrdersView,
            meta: { requiresAuth: true },
        },
        {
            path: "/admin/users",
            name: "admin.users",
            component: UsersView,
            meta: { requiresAuth: true, roles: [1] },
        },
        {
            path: "/admin/branches",
            name: "admin.branches",
            component: BranchesView,
            meta: { requiresAuth: true, roles: [1] },
        },
        { path: "/:pathMatch(.*)*", redirect: "/" },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (to.path === "/login") {
        if (auth.isAuthenticated) return { path: "/" };
        return true;
    }

    if (!to.meta?.requiresAuth) return true;

    if (!auth.isAuthenticated) {
        return { path: "/login", query: { next: to.fullPath } };
    }

    if (!auth.user && !auth.isBootstrapping) {
        try {
            await auth.fetchMe();
        } catch (err) {
            auth.clearAuth();
            return { path: "/login", query: { next: to.fullPath } };
        }
    }

    const allowedRoles = to.meta?.roles;
    if (Array.isArray(allowedRoles) && allowedRoles.length > 0) {
        const roleId = auth.roleId;
        if (!roleId || !allowedRoles.includes(roleId)) {
            return { path: "/" };
        }
    }

    return true;
});

export default router;
