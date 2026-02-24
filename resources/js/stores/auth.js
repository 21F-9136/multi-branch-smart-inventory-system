import { defineStore } from "pinia";
import { api, getToken, setToken } from "../lib/api";

const USER_KEY = "erp.user";

function loadUser() {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function persistUser(user) {
    if (!user) {
        localStorage.removeItem(USER_KEY);
        return;
    }
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export const useAuthStore = defineStore("auth", {
    state: () => ({
        token: getToken(),
        user: loadUser(),
        isBootstrapping: false,
    }),

    getters: {
        isAuthenticated: (state) => Boolean(state.token),
        roleId: (state) =>
            state.user?.role_id ? Number(state.user.role_id) : null,
        isSuperAdmin() {
            return this.roleId === 1;
        },
    },

    actions: {
        async login({ email, password }) {
            const res = await api.post("/login", { email, password });
            this.token = res.data?.token;
            this.user = res.data?.user ?? null;

            setToken(this.token);
            persistUser(this.user);
        },

        async fetchMe() {
            if (!this.token) return null;

            this.isBootstrapping = true;
            try {
                const res = await api.get("/me");
                this.user = res.data;
                persistUser(this.user);
                return this.user;
            } finally {
                this.isBootstrapping = false;
            }
        },

        async logout() {
            try {
                await api.post("/logout");
            } catch {
                // ignore
            }
            this.clearAuth();
        },

        clearAuth() {
            this.token = null;
            this.user = null;
            setToken(null);
            persistUser(null);
        },
    },
});
