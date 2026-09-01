<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
const props = defineProps({ rows: Array, from: String, to: String });
const from = ref(props.from); const to = ref(props.to);
const isOwner = computed(() => usePage().props.auth.user.role === 'owner');
function run() { router.get(route('reports.products'), { from: from.value, to: to.value }); }
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
</script>
<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">تقرير المنتجات</h1>
                <div class="mb-4 flex flex-wrap gap-2 text-[12px]">
            <a v-if="isOwner" :href="route('reports.profitability')" class="utu-btn-ghost !py-1">الأرباح</a>
            <a :href="route('reports.customers')" class="utu-btn-ghost !py-1">العملاء</a>
            <a :href="route('reports.sales-representatives')" class="utu-btn-ghost !py-1">المندوبين</a>
            <a :href="route('reports.products')" class="utu-btn-ghost !py-1">المنتجات</a>
            <a :href="route('reports.areas')" class="utu-btn-ghost !py-1">المناطق</a>
        </div>
        <div class="utu-card mb-4 flex flex-wrap items-end gap-2">
            <div><label class="utu-label">من</label><input v-model="from" type="date" class="utu-input"></div>
            <div><label class="utu-label">إلى</label><input v-model="to" type="date" class="utu-input"></div>
            <button class="utu-btn-gold" @click="run">تطبيق</button>
        </div>
        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-3 py-2 text-start">المنتج</th><th class="px-3 py-2 text-start">الوحدات المباعة</th><th class="px-3 py-2 text-start">إجمالي المبيعات</th><th v-if="isOwner" class="px-3 py-2 text-start">FIFO COGS</th><th v-if="isOwner" class="px-3 py-2 text-start">الربح الإجمالي</th><th class="px-3 py-2 text-start">المخزون الحالي</th><th class="px-3 py-2"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="r.id" class="border-t border-grey-2">
                        <td class="px-3 py-2 font-medium">{{ r.name }}</td>
                        <td class="px-3 py-2">{{ r.units_sold }}</td>
                        <td class="px-3 py-2">{{ formatIqd(r.gross_sales) }}</td>
                        <td v-if="isOwner" class="px-3 py-2">{{ formatIqd(r.cogs) }}</td>
                        <td v-if="isOwner" class="px-3 py-2 font-semibold text-gold">{{ formatIqd(r.gross_profit) }}</td>
                        <td class="px-3 py-2" :class="r.current_stock < 0 ? 'font-semibold text-danger' : ''">{{ r.current_stock }}</td>
                        <td class="px-3 py-2 text-end"><a :href="route('reports.products.detail', r.id)" class="utu-btn-ghost !px-2 !py-1 !text-[11px]">تفاصيل</a></td>
                    </tr>
                    <tr v-if="rows.length === 0"><td colspan="7" class="px-4 py-8 text-center text-grey-text">لا توجد مبيعات لهذه الفترة.</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
