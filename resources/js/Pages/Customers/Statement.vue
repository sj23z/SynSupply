<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerStatementTable from '@/Components/CustomerStatementTable.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ customer: Object, from: String, to: String, lines: Array, opening_balance: Number, closing_balance: Number });
const from = ref(props.from);
const to = ref(props.to);
function run() { router.get(route('customers.statement', props.customer.id), { from: from.value, to: to.value }); }
</script>

<template>
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">كشف حساب — {{ customer.name }}</h1>
            <a :href="route('customers.statement.pdf', customer.id) + `?from=${from}&to=${to}`" target="_blank" class="utu-btn-gold">طباعة / PDF</a>
        </div>

        <div class="utu-card mb-4 flex flex-wrap items-end gap-2">
            <div><label class="utu-label">من</label><input v-model="from" type="date" class="utu-input"></div>
            <div><label class="utu-label">إلى</label><input v-model="to" type="date" class="utu-input"></div>
            <button class="utu-btn-ghost" @click="run">تطبيق</button>
        </div>

        <div class="mb-3 flex flex-wrap gap-4 text-[11px]">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-danger"></span> فاتورة (مدين)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2E5FA3]"></span> دفعة (دائن)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#3E7A4F]"></span> مردود (دائن)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-grey-3"></span> تسوية / خصم (دائن، بدون نقد فعلي)</span>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <CustomerStatementTable :lines="lines" :opening-balance="opening_balance" :closing-balance="closing_balance" />
        </div>
    </AppLayout>
</template>
