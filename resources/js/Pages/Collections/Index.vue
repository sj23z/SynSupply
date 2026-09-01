<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({ payments: Object, filters: Object });
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
const methodLabels = { cash: 'نقدي', bank_transfer: 'حوالة بنكية', other: 'أخرى', settlement: 'تسوية', discount: 'خصم' };
function destroy(p) {
    if (confirm('حذف هذه الدفعة؟')) router.delete(route('collections.destroy', p.id));
}
</script>

<template>
    <AppLayout>
        <FlashBanner />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">التحصيلات</h1>
            <Link :href="route('collections.create')" class="utu-btn-gold">دفعة جديدة</Link>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-4 py-2.5 text-start">العميل</th><th class="px-4 py-2.5 text-start">التاريخ</th><th class="px-4 py-2.5 text-start">المبلغ</th><th class="px-4 py-2.5 text-start">الطريقة</th><th class="px-4 py-2.5 text-start">الفاتورة</th><th class="px-4 py-2.5"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="p in payments.data" :key="p.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ p.customer?.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ p.payment_date }}</td>
                        <td class="px-4 py-2.5">{{ formatIqd(p.amount) }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ methodLabels[p.method] }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ p.invoice?.invoice_number || '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <Link :href="route('collections.edit', p.id)" class="utu-btn-ghost !px-3 !py-1.5">تعديل</Link>
                            <button class="utu-btn-ghost !border-danger/30 !px-3 !py-1.5 !text-danger" @click="destroy(p)">حذف</button>
                        </td>
                    </tr>
                    <tr v-if="payments.data.length === 0"><td colspan="6" class="px-4 py-8 text-center text-grey-text">لا توجد دفعات مسجلة بعد.</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
