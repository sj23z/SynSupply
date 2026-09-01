<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({ product: Object, transactions: Array, from: String, to: String, isOwner: Boolean });
const from = ref(props.from);
const to = ref(props.to);
function run() { router.get(route('reports.products.detail', props.product.id), { from: from.value, to: to.value }); }
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }

const expanded = reactive({});
function toggle(i) { expanded[i] = !expanded[i]; }
</script>

<template>
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">تقرير منتج: {{ props.product.name }}</h1>
            <a :href="route('reports.products.detail.pdf', props.product.id) + '?from=' + from + '&to=' + to" target="_blank" class="utu-btn-ghost">طباعة / PDF</a>
        </div>

        <div class="utu-card mb-4 flex flex-wrap items-end gap-2">
            <div><label class="utu-label">من</label><input v-model="from" type="date" class="utu-input"></div>
            <div><label class="utu-label">إلى</label><input v-model="to" type="date" class="utu-input"></div>
            <button class="utu-btn-gold" @click="run">تطبيق</button>
            <a :href="route('reports.products')" class="utu-btn-ghost">رجوع للتقرير</a>
        </div>

        <div class="mb-4 grid gap-4" :class="isOwner ? 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-6' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'">
            <div class="utu-card"><p class="text-[12px] text-grey-text">الكمية المباعة</p><p class="mt-1 text-lg font-bold">{{ product.units_sold }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">الكمية المرتجعة</p><p class="mt-1 text-lg font-bold">{{ product.returned_qty }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">صافي الكمية المباعة</p><p class="mt-1 text-lg font-bold">{{ product.net_qty_sold }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">إجمالي المبيعات</p><p class="mt-1 text-lg font-bold">{{ formatIqd(product.gross_sales) }}</p></div>
            <template v-if="isOwner">
                <div class="utu-card"><p class="text-[12px] text-grey-text">FIFO COGS</p><p class="mt-1 text-lg font-bold">{{ formatIqd(product.cogs) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">الربح الإجمالي</p><p class="mt-1 text-lg font-bold text-gold">{{ formatIqd(product.gross_profit) }}</p></div>
            </template>
            <div class="utu-card"><p class="text-[12px] text-grey-text">المخزون الحالي</p><p class="mt-1 text-lg font-bold" :class="product.current_stock < 0 ? 'text-danger' : ''">{{ product.current_stock }}</p></div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-3 py-2 text-start">الفاتورة</th><th class="px-3 py-2 text-start">التاريخ</th><th class="px-3 py-2 text-start">العميل</th>
                        <th class="px-3 py-2 text-start">المنطقة</th><th class="px-3 py-2 text-start">المندوب</th>
                        <th class="px-3 py-2 text-start">الكمية</th><th class="px-3 py-2 text-start">السعر</th><th class="px-3 py-2 text-start">الخصم</th>
                        <th v-if="isOwner" class="px-3 py-2 text-start">COGS</th>
                        <th class="px-3 py-2 text-start">الإجمالي</th>
                        <th class="px-3 py-2 text-start">مرتجع</th><th class="px-3 py-2 text-start">حالة الدفع</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(t, i) in transactions" :key="i">
                    <tr class="border-t border-grey-2">
                        <td class="px-3 py-2">{{ t.invoice_number }}</td>
                        <td class="px-3 py-2">{{ t.date }}</td>
                        <td class="px-3 py-2">{{ t.customer_name }}</td>
                        <td class="px-3 py-2 text-grey-text">{{ t.area_name || '—' }}</td>
                        <td class="px-3 py-2 text-grey-text">{{ t.sales_rep_name || '—' }}</td>
                        <td class="px-3 py-2">{{ t.qty }}</td>
                        <td class="px-3 py-2">{{ formatIqd(t.unit_price) }}</td>
                        <td class="px-3 py-2">{{ t.discount_type ? (t.discount_type === 'percent' ? t.discount_value + '%' : formatIqd(t.discount_value)) : '—' }}</td>
                        <td v-if="isOwner" class="px-3 py-2">{{ formatIqd(t.cogs_total) }}</td>
                        <td class="px-3 py-2 font-medium">{{ formatIqd(t.line_total) }}</td>
                        <td class="px-3 py-2" :class="t.returned_qty > 0 ? 'text-[#3E7A4F] font-medium' : 'text-grey-text'">{{ t.returned_qty > 0 ? t.returned_qty : '—' }}</td>
                        <td class="px-3 py-2 text-grey-text">{{ { unpaid: 'غير مدفوعة', partial: 'جزئية', paid: 'مدفوعة' }[t.invoice_payment_status] }}</td>
                        <td class="px-3 py-2 text-end">
                            <button v-if="t.payments.length || t.returns.length" class="text-[11px] text-grey-text underline" @click="toggle(i)">
                                {{ expanded[i] ? 'إخفاء' : 'الحركات المالية' }}
                            </button>
                        </td>
                    </tr>
                    <!-- Related financial movements: every payment row linked to this
                         invoice shown separately (never collapsed into one "Paid"
                         figure — one invoice can have multiple payment rows), plus
                         full return detail for this specific line. -->
                    <tr v-if="expanded[i] && (t.payments.length || t.returns.length)" class="border-t border-grey-2 bg-grey-1/50">
                        <td :colspan="isOwner ? 12 : 11" class="px-3 py-2">
                            <div v-if="t.payments.length" class="mb-2">
                                <p class="mb-1 text-[11px] font-semibold text-grey-text">الحركات المالية المرتبطة بالفاتورة</p>
                                <div v-for="(p, j) in t.payments" :key="j" class="flex items-center gap-3 border-r-2 py-1 pr-2 text-[12px]" style="border-color:#2E5FA3">
                                    <span class="text-grey-text">{{ p.date }}</span>
                                    <span class="font-medium" style="color:#2E5FA3">{{ p.method_label }}</span>
                                    <span class="font-medium">{{ formatIqd(p.amount) }}</span>
                                </div>
                            </div>
                            <div v-if="t.returns.length">
                                <p class="mb-1 text-[11px] font-semibold text-grey-text">المردودات المرتبطة بهذا السطر</p>
                                <div v-for="(r, j) in t.returns" :key="j" class="flex items-center gap-3 border-r-2 py-1 pr-2 text-[12px]" style="border-color:#3E7A4F">
                                    <span class="text-grey-text">{{ r.return_date }}</span>
                                    <span class="font-medium" style="color:#3E7A4F">{{ r.return_number }}</span>
                                    <span>{{ r.qty }} × </span>
                                    <span class="font-medium">{{ formatIqd(r.value) }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </template>
                    <tr v-if="transactions.length === 0"><td :colspan="isOwner ? 12 : 11" class="px-4 py-8 text-center text-grey-text">لا توجد مبيعات لهذه الفترة.</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
