<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Array,
    filters: Object,
    summary: Object,
});

const search = ref(props.filters.search ?? '');
function runSearch() {
    router.get(route('inventory.index'), { search: search.value }, { preserveState: true, replace: true });
}

function formatIqd(v) {
    return new Intl.NumberFormat('en-US').format(v) + ' د.ع';
}

const expiryLabels = {
    expired: 'منتهي الصلاحية',
    within_30: 'خلال 30 يوم',
    within_60: 'خلال 60 يوم',
    within_90: 'خلال 90 يوم',
};
const expiryClasses = {
    expired: 'bg-danger/10 text-danger',
    within_30: 'bg-danger/10 text-danger',
    within_60: 'bg-gold-soft/40 text-ink',
    within_90: 'bg-grey-2 text-grey-text',
};
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">المخزون</h1>
            <div class="flex gap-2">
                <input v-model="search" @input="runSearch" type="text" placeholder="بحث بالاسم…" class="utu-input w-56">
                <Link :href="route('inventory.create')" class="utu-btn-gold">إدخال مخزون</Link>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="utu-card">
                <p class="text-[12px] text-grey-text">قيمة المخزون</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ formatIqd(summary.total_value) }}</p>
            </div>
            <div class="utu-card">
                <p class="text-[12px] text-grey-text">مخزون سالب</p>
                <p class="mt-1 text-lg font-bold" :class="summary.negative_count > 0 ? 'text-danger' : 'text-ink'">{{ summary.negative_count }}</p>
            </div>
            <div class="utu-card">
                <p class="text-[12px] text-grey-text">مخزون منخفض</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ summary.low_stock_count }}</p>
            </div>
            <div class="utu-card">
                <p class="text-[12px] text-grey-text">قرب انتهاء الصلاحية (٩٠ يوم)</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ summary.expiring_soon_count }}</p>
            </div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">المنتج</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الكمية</th>
                        <th class="px-4 py-2.5 text-start font-semibold">قيمة المخزون</th>
                        <th class="px-4 py-2.5 text-start font-semibold">عدد الدفعات</th>
                        <th class="px-4 py-2.5 text-start font-semibold">أقرب انتهاء صلاحية</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products" :key="p.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ p.name }}</td>
                        <td class="px-4 py-2.5" :class="p.is_negative ? 'font-semibold text-danger' : (p.is_low_stock ? 'font-semibold text-gold' : '')">
                            {{ p.quantity }}
                            <span v-if="p.is_negative" class="ms-1 rounded-utu bg-danger/10 px-1.5 py-0.5 text-[10px]">سالب</span>
                            <span v-else-if="p.is_low_stock" class="ms-1 rounded-utu bg-gold-soft/40 px-1.5 py-0.5 text-[10px] text-ink">منخفض</span>
                        </td>
                        <td class="px-4 py-2.5 text-grey-text">{{ formatIqd(p.inventory_value) }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ p.batch_count }}</td>
                        <td class="px-4 py-2.5">
                            <span v-if="p.nearest_expiry" class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="expiryClasses[p.expiry_status] || 'text-grey-text'">
                                {{ p.nearest_expiry }} — {{ expiryLabels[p.expiry_status] || '' }}
                            </span>
                            <span v-else class="text-grey-text">—</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <Link :href="route('inventory.show', p.id)" class="utu-btn-ghost !px-3 !py-1.5">الدفعات</Link>
                        </td>
                    </tr>
                    <tr v-if="products.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-grey-text">لا توجد بيانات مخزون بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
