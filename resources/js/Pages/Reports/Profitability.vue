<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ from: String, to: String, netSales: Number, cogs: Number, returnsValue: Number, grossProfit: Number, operatingExpenses: Number, operatingProfit: Number });
const from = ref(props.from);
const to = ref(props.to);
function run() { router.get(route('reports.profitability'), { from: from.value, to: to.value }); }
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">تقرير الأرباح</h1>
                <div class="mb-4 flex flex-wrap gap-2 text-[12px]">
            <a :href="route('reports.profitability')" class="utu-btn-ghost !py-1">الأرباح</a>
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

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="utu-card"><p class="text-[12px] text-grey-text">صافي المبيعات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(netSales) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">تكلفة البضاعة المباعة (FIFO)</p><p class="mt-1 text-lg font-bold">{{ formatIqd(cogs) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">قيمة المردودات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(returnsValue) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">الربح الإجمالي</p><p class="mt-1 text-lg font-bold text-gold">{{ formatIqd(grossProfit) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">المصروفات التشغيلية</p><p class="mt-1 text-lg font-bold">{{ formatIqd(operatingExpenses) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">الربح التشغيلي</p><p class="mt-1 text-lg font-bold" :class="operatingProfit >= 0 ? 'text-gold' : 'text-danger'">{{ formatIqd(operatingProfit) }}</p></div>
        </div>
    </AppLayout>
</template>
