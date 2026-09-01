<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ return: Object, canEdit: Boolean, canFinalize: Boolean, canCancel: Boolean, canDelete: Boolean });
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
const statusLabels = { draft: 'مسودة', finalized: 'معتمد', cancelled: 'ملغى' };

function finalize() {
    if (confirm('اعتماد المردود؟ سيتم إعادة الكمية إلى المخزون.')) {
        router.post(route('sales-returns.finalize', props.return.id));
    }
}
function destroy() {
    if (confirm('حذف هذه المسودة؟')) router.delete(route('sales-returns.destroy', props.return.id));
}

const showCancel = ref(false);
const cancelReason = ref('');
function submitCancel() {
    router.post(route('sales-returns.cancel', props.return.id), { reason: cancelReason.value }, { onSuccess: () => (showCancel.value = false) });
}
</script>

<template>
    <AppLayout>
        <FlashBanner />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-base font-bold text-ink">{{ props.return.return_number || 'مردود (مسودة)' }}</h1>
                <span class="mt-1 inline-block rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="{ 'bg-grey-2 text-grey-text': props.return.status === 'draft', 'bg-gold-soft/40 text-ink': props.return.status === 'finalized', 'bg-danger/10 text-danger': props.return.status === 'cancelled' }">{{ statusLabels[props.return.status] }}</span>
            </div>
            <div class="flex gap-2">
                <a v-if="props.return.status !== 'draft'" :href="route('sales-returns.pdf', props.return.id)" target="_blank" class="utu-btn-ghost">طباعة / PDF</a>
                <a v-if="canEdit" :href="route('sales-returns.edit', props.return.id)" class="utu-btn-ghost">تعديل</a>
                <button v-if="canFinalize" class="utu-btn-gold" @click="finalize">اعتماد المردود</button>
                <button v-if="canCancel" class="utu-btn-ghost !border-danger/30 !text-danger" @click="showCancel = true">إلغاء المردود</button>
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

        <div v-if="props.return.status === 'cancelled'" class="utu-card mb-4 !border-danger/20 bg-danger/5">
            <p class="text-[12px] text-grey-text">تم إلغاء هذا المردود</p>
            <p v-if="props.return.cancelledBy?.name" class="mt-1 text-[13px]">بواسطة: <span class="font-medium">{{ props.return.cancelledBy.name }}</span></p>
            <p v-if="props.return.cancelled_at" class="mt-1 text-[12px] text-grey-text">{{ props.return.cancelled_at }}</p>
            <p v-if="props.return.cancel_reason" class="mt-1 text-[13px]">السبب: {{ props.return.cancel_reason }}</p>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3 text-[13px]">
            <div class="utu-card"><p class="text-[12px] text-grey-text">العميل</p><p class="font-medium">{{ props.return.customer?.name }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">التاريخ</p><p class="font-medium">{{ props.return.return_date }}</p></div>
            <div class="utu-card"><p class="text-[12px] text-grey-text">الفاتورة الأصلية</p><p class="font-medium">{{ props.return.invoice?.invoice_number || '—' }}</p></div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-3 py-2 text-start">المنتج</th><th class="px-3 py-2 text-start">الكمية</th><th class="px-3 py-2 text-start">السعر</th><th class="px-3 py-2 text-start">الإجمالي</th></tr>
                </thead>
                <tbody>
                    <tr v-for="item in props.return.items" :key="item.id" class="border-t border-grey-2">
                        <td class="px-3 py-2">{{ item.product?.name }}</td>
                        <td class="px-3 py-2">{{ item.qty }}</td>
                        <td class="px-3 py-2">{{ formatIqd(item.unit_price) }}</td>
                        <td class="px-3 py-2 font-medium">{{ formatIqd(item.line_total) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <p class="mt-4 text-end text-lg font-bold">{{ formatIqd(props.return.total_value) }}</p>
    </AppLayout>
</template>
