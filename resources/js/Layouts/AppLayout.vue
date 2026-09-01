<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const mobileMenuOpen = ref(false);

// Both roles see all of these — Products/Areas/Sales Reps stay visible to
// Staff as read-only lists (needed for dropdowns and lookup); the pages
// themselves hide create/edit controls when the user isn't Admin, and the
// server enforces the same rule independently via each Policy.
const navItems = computed(() => {
    const role = user.value?.role;

    if (role === 'sales_rep') {
        return [
            { label: 'لوحة التحكم', href: route('dashboard'), active: route().current('dashboard') },
            { label: 'عملائي', href: route('customers.index'), active: route().current('customers.*') },
            { label: 'المنتجات', href: route('products.index'), active: route().current('products.*') },
        ];
    }

    const items = [
        { label: 'لوحة التحكم', href: route('dashboard'), active: route().current('dashboard') },
        { label: 'الفواتير', href: route('invoices.index'), active: route().current('invoices.*') },
        { label: 'مردودات المبيعات', href: route('sales-returns.index'), active: route().current('sales-returns.*') },
        { label: 'العملاء', href: route('customers.index'), active: route().current('customers.*') },
        { label: 'المنتجات', href: route('products.index'), active: route().current('products.*') },
        { label: 'المندوبين', href: route('sales-representatives.index'), active: route().current('sales-representatives.*') },
        { label: 'المناطق', href: route('areas.index'), active: route().current('areas.*') },
    ];

    if (role === 'admin' || role === 'owner') {
        items.push({ label: 'المخزون', href: route('inventory.index'), active: route().current('inventory.*') });
        items.push({ label: 'التحصيلات', href: route('collections.index'), active: route().current('collections.*') });
        items.push({ label: 'المصروفات', href: route('expenses.index'), active: route().current('expenses.*') });
        items.push({ label: 'التقارير', href: route('reports.customers'), active: route().current('reports.*') && !route().current('reports.profitability') });
    }

    if (role === 'owner') {
        items.push({ label: 'الربحية', href: route('reports.profitability'), active: route().current('reports.profitability') });
        items.push({ label: 'المستخدمون', href: route('users.index'), active: route().current('users.*') });
    }

    return items;
});

const roleLabel = computed(() => ({ admin: 'مدير', sales_rep: 'مندوب مبيعات', owner: 'مالك' }[user.value?.role] ?? 'موظف'));

function logout() {
    mobileMenuOpen.value = false;
    router.post(route('logout'));
}

function closeMenu() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-grey-1">
        <!-- App bar: ported from the invoice prototype's .app-bar (ink background, gold dot).
             Mobile: only the brand + a hamburger trigger stay in the bar itself; user info,
             role badge, password link, and logout move into the mobile drawer below. -->
        <header class="sticky top-0 z-50 flex items-center justify-between gap-4 bg-ink px-4 py-3.5 text-white sm:px-7">
            <div class="flex items-center gap-2.5 text-sm font-bold tracking-wide">
                <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-gold"></span>
                <div>
                    UTU
                    <small class="block text-[10px] font-normal uppercase tracking-widest text-white/60">
                        نظام إدارة UTU
                    </small>
                </div>
            </div>

            <!-- Desktop: full user info row -->
            <div class="hidden items-center gap-3 text-[12.5px] md:flex">
                <span class="text-white/70">{{ user?.name }}</span>
                <span class="rounded-utu border border-white/25 px-2 py-0.5 text-[11px] font-semibold">
                    {{ roleLabel }}
                </span>
                <Link :href="route('profile.edit')" class="utu-btn-ghost !border-white/20 !bg-transparent !text-white hover:!border-white/55">
                    كلمة المرور
                </Link>
                <button type="button" class="utu-btn-ghost !border-white/20 !bg-transparent !text-white hover:!border-white/55" @click="logout">
                    تسجيل الخروج
                </button>
            </div>

            <!-- Mobile: hamburger trigger only -->
            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-utu border border-white/25 text-white md:hidden"
                aria-label="فتح القائمة"
                @click="mobileMenuOpen = true"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </header>

        <!-- Mobile drawer: slides in from the right (matches the sidebar's visual
             position in this RTL layout — it's the first flex child in the desktop
             row below, which puts it on the right). Backdrop closes on click. -->
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-[60] md:hidden">
            <div class="absolute inset-0 bg-black/40" @click="closeMenu"></div>
            <div class="absolute right-0 top-0 flex h-full w-72 max-w-[85vw] flex-col overflow-y-auto bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-grey-2 bg-ink px-4 py-3.5 text-white">
                    <div class="text-[12.5px]">
                        <p class="font-semibold">{{ user?.name }}</p>
                        <p class="mt-0.5 text-white/60">{{ roleLabel }}</p>
                    </div>
                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-utu border border-white/25 text-white" aria-label="إغلاق القائمة" @click="closeMenu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex flex-col gap-1 p-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-utu px-3 py-2.5 text-[13.5px] font-medium text-ink hover:bg-grey-1"
                        :class="{ 'bg-grey-1 font-semibold': item.active }"
                        @click="closeMenu"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="mt-auto flex flex-col gap-1 border-t border-grey-2 p-2">
                    <Link :href="route('profile.edit')" class="rounded-utu px-3 py-2.5 text-[13.5px] font-medium text-ink hover:bg-grey-1" @click="closeMenu">
                        كلمة المرور
                    </Link>
                    <button type="button" class="rounded-utu px-3 py-2.5 text-start text-[13.5px] font-medium text-danger hover:bg-grey-1" @click="logout">
                        تسجيل الخروج
                    </button>
                </div>
            </div>
        </div>

        <div class="mx-auto flex max-w-7xl gap-6 px-3 py-4 sm:px-4 md:px-6 md:py-6">
            <!-- Sidebar nav: desktop/tablet only from md: up. Hidden on mobile —
                 the drawer above provides the same navigation there. -->
            <aside class="hidden w-56 shrink-0 md:block">
                <nav class="utu-card flex flex-col gap-1 p-2">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-utu px-3 py-2 text-[13px] font-medium text-ink hover:bg-grey-1"
                        :class="{ 'bg-grey-1 font-semibold': item.active }"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </aside>

            <main class="min-w-0 flex-1 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>
