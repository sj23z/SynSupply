<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerStatementTable from '@/Components/CustomerStatementTable.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({ customers: Array, totals: Object, from: String, to: String });
const from = ref(props.from);
const to = ref(props.to);
const isOwner = computed(() => usePage().props.auth.user.role === 'owner');
function run() { router.get(route('reports.customers'), { from: from.value, to: to.value }); }
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }

const expandedCustomers = reactive({});
function toggleCustomer(id) { expandedCustomers[id] = !expandedCustomers[id]; }
function expandAll() { props.customers.forEach((c) => (expandedCustomers[c.id] = true)); }
function collapseAll() { props.customers.forEach((c) => (expandedCustomers[c.id] = false)); }
</script>

<template>
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">تقرير العملاء</h1>
            <a :href="route('reports.customers.pdf') + '?from=' + from + '&to=' + to" target="_blank" class="utu-btn-gold">طباعة / PDF</a>
        </div>

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
            <span class="mx-1 hidden text-grey-2 sm:inline">|</span>
            <button class="utu-btn-ghost !text-[12px]" @click="expandAll">توسيع الكل</button>
            <button class="utu-btn-ghost !text-[12px]" @click="collapseAll">طي الكل</button>
        </div>

        <div class="mb-3 flex flex-wrap gap-4 text-[11px]">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-danger"></span> فاتورة (مدين)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2E5FA3]"></span> دفعة (دائن)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#3E7A4F]"></span> مردود (دائن)</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-grey-3"></span> تسوية / خصم</span>
        </div>

        <!-- CUSTOMERS TOTALS -->
        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="utu-card"><p class="text-[12px] text-grey-text">عدد العملاء</p><p class="mt-1 text-lg font-bold">{{ totals.customers_count }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">إجمالي المبيعات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.gross_sales) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">المردودات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.returns) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">صافي المبيعات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.net_sales) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">نقدي</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.cash) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">حوالة بنكية</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.bank_transfer) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">أخرى</p><p class="mt-1 text-lg font-bold">{{ formatIqd(totals.other) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">النقد الفعلي المستلم</p><p class="mt-1 text-lg font-bold text-gold">{{ formatIqd(totals.actual_cash_received) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">تسوية</p><p class="mt-1 text-lg font-bold text-grey-text">{{ formatIqd(totals.settlement) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">خصم</p><p class="mt-1 text-lg font-bold text-grey-text">{{ formatIqd(totals.discount) }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">الرصيد المستحق</p><p class="mt-1 text-lg font-bold" :class="totals.outstanding_balance > 0 ? 'text-danger' : ''">{{ formatIqd(totals.outstanding_balance) }}</p></div>
        </div>

        <!-- HIERARCHY: every customer, each expandable to their full statement, right here -->
        <h2 class="mb-2 text-[13px] font-bold text-grey-text">العملاء ({{ customers.length }})</h2>
        <div class="space-y-3">
            <div v-for="c in customers" :key="c.id" class="utu-card !p-0 overflow-hidden">
                <button type="button" class="flex w-full flex-wrap items-center justify-between gap-2 p-3 text-start" @click="toggleCustomer(c.id)">
                    <div>
                        <p class="font-semibold text-ink">{{ c.name }}</p>
                        <p class="text-[11px] text-grey-text">{{ c.phone || '—' }}<span v-if="c.address"> · {{ c.address }}</span></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-[12px]">
                        <span class="text-grey-text">صافي المبيعات: <span class="font-medium text-ink">{{ formatIqd(c.totals.net_sales) }}</span></span>
                        <span class="text-grey-text">الرصيد: <span class="font-medium" :class="c.outstanding_balance > 0 ? 'text-danger' : 'text-ink'">{{ formatIqd(c.outstanding_balance) }}</span></span>
                        <a :href="route('customers.statement', c.id)" class="utu-btn-ghost !px-2 !py-1 !text-[11px]" @click.stop>كشف مستقل</a>
                        <span class="text-grey-text">{{ expandedCustomers[c.id] ? '▲' : '▼' }}</span>
                    </div>
                </button>
                <div v-if="expandedCustomers[c.id]" class="border-t border-grey-2">
                    <CustomerStatementTable :lines="c.lines" :opening-balance="c.opening_balance" :closing-balance="c.closing_balance" dense />
                </div>
            </div>
            <div v-if="customers.length === 0" class="utu-card text-center text-grey-text">لا يوجد عملاء ذوو حركة لهذه الفترة.</div>
        </div>
    </AppLayout>
</template>
