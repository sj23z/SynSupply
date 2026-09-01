<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ invoice: Object, isSalesRep: Boolean, canEdit: Boolean, canFinalize: Boolean, canCancel: Boolean, canDelete: Boolean });

function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
const statusLabels = { draft: 'مسودة', finalized: 'معتمدة', cancelled: 'ملغاة' };

function finalize() {
    if (confirm('اعتماد الفاتورة؟ سيتم خصم الكمية من المخزون وتوليد رقم الفاتورة.')) {
        router.post(route('invoices.finalize', props.invoice.id));
    }
}
const showCancel = ref(false);
const cancelReason = ref('');
function submitCancel() {
    router.post(route('invoices.cancel', props.invoice.id), { reason: cancelReason.value }, { onSuccess: () => (showCancel.value = false) });
}
function destroy() {
    if (confirm('حذف هذه المسودة؟')) router.delete(route('invoices.destroy', props.invoice.id));
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-base font-bold text-ink">{{ invoice.invoice_number || 'فاتورة (مسودة)' }}</h1>
                <span class="mt-1 inline-block rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="{ 'bg-grey-2 text-grey-text': invoice.status === 'draft', 'bg-gold-soft/40 text-ink': invoice.status === 'finalized', 'bg-danger/10 text-danger': invoice.status === 'cancelled' }">
                    {{ statusLabels[invoice.status] }}
                </span>
            </div>
            <div class="flex gap-2">
                <a v-if="invoice.status !== 'draft'" :href="route('invoices.pdf', invoice.id)" target="_blank" class="utu-btn-ghost">طباعة / PDF</a>
                <a v-if="invoice.status === 'finalized'" :href="route('sales-returns.create', { invoice_id: invoice.id })" class="utu-btn-ghost">إنشاء مردود</a>
                <a v-if="invoice.status === 'finalized' && !isSalesRep" :href="route('collections.create', { customer_id: invoice.customer_id })" class="utu-btn-ghost">تسجيل تحصيل</a>
                <a v-if="canEdit" :href="route('invoices.edit', invoice.id)" class="utu-btn-ghost">تعديل</a>
                <button v-if="canFinalize" class="utu-btn-gold" @click="finalize">اعتماد الفاتورة</button>
                <button v-if="canCancel" class="utu-btn-ghost !border-danger/30 !text-danger" @click="showCancel = true">إلغاء الفاتورة</button>
                <button v-if="canDelete" class="utu-btn-ghost !border-danger/30 !text-danger" @click="destroy">حذف المسودة</button>
            </div>
        </div>

        <div v-if="showCancel" class="utu-card mb-4">
            <label class="utu-label">سبب الإلغاء — اختياري</label>
            <input v-model="cancelReason" type="text" class="utu-input">
            <div class="mt-2 flex gap-2">
                <button class="utu-btn-dark" @click="submitCancel">تأكيد الإلغاء</button>
                <button class="utu-btn-ghost" @click="showCancel = false">تراجع</button>
            </div>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3 text-[13px]">
            <div class="utu-card"><p class="text-[12px] text-grey-text">العميل</p><p class="font-medium">{{ invoice.customer?.name }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">التاريخ</p><p class="font-medium">{{ invoice.invoice_date }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">المندوب</p><p class="font-medium">{{ invoice.salesRepresentative?.name || '—' }}</p></div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-3 py-2 text-start">المنتج</th><th class="px-3 py-2 text-start">الكمية</th><th class="px-3 py-2 text-start">السعر</th><th class="px-3 py-2 text-start">الإجمالي</th></tr>
                </thead>
                <tbody>
                    <tr v-for="item in invoice.items" :key="item.id" class="border-t border-grey-2">
                        <td class="px-3 py-2">{{ item.product?.name }}</td>
                        <td class="px-3 py-2">{{ item.qty }}</td>
                        <td class="px-3 py-2">{{ formatIqd(item.unit_price) }}</td>
                        <td class="px-3 py-2 font-medium">{{ formatIqd(item.line_total) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <div v-if="!isSalesRep" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="hidden md:block"></div>
            <div class="utu-card">
                <table class="w-full text-[13px]">
                    <tr><td class="py-1 text-grey-text">المجموع الفرعي</td><td class="py-1 text-end">{{ formatIqd(invoice.subtotal) }}</td></tr>
                    <tr><td class="py-1 text-grey-text">خصم العناصر</td><td class="py-1 text-end">{{ formatIqd(invoice.item_discount_total) }}</td></tr>
                    <tr><td class="py-1 text-grey-text">خصم الفاتورة</td><td class="py-1 text-end">{{ formatIqd(invoice.invoice_discount_amount) }}</td></tr>
                    <tr class="border-t border-grey-2 font-bold"><td class="py-2">الإجمالي</td><td class="py-2 text-end">{{ formatIqd(invoice.grand_total) }}</td></tr>
                    <tr><td class="py-1 text-grey-text">المدفوع</td><td class="py-1 text-end">{{ formatIqd(invoice.amount_paid_cached) }}</td></tr>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
