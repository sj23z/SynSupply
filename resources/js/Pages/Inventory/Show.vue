<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ product: Object, batches: Array });

function formatIqd(v) {
    return new Intl.NumberFormat('en-US').format(v) + ' د.ع';
}

const expiryClasses = {
    expired: 'bg-danger/10 text-danger',
    within_30: 'bg-danger/10 text-danger',
    within_60: 'bg-gold-soft/40 text-ink',
    within_90: 'bg-grey-2 text-grey-text',
};
</script>

<template>
    <AppLayout>
        <div class="mb-4">
            <h1 class="text-base font-bold text-ink">دفعات المخزون — {{ product.name }}</h1>
            <p class="mt-1 text-[12.5px]" :class="product.cached_stock_qty < 0 ? 'font-semibold text-danger' : 'text-grey-text'">
                الكمية الحالية: {{ product.cached_stock_qty }}
            </p>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">تاريخ الإدخال</th>
                        <th class="px-4 py-2.5 text-start font-semibold">رقم الدفعة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الكمية المستلمة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">المتبقي</th>
                        <th class="px-4 py-2.5 text-start font-semibold">تكلفة الوحدة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">القيمة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">انتهاء الصلاحية</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in batches" :key="b.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5">{{ b.entry_date }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ b.batch_number || '—' }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ b.quantity_received }}</td>
                        <td class="px-4 py-2.5">{{ b.quantity_remaining }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ formatIqd(b.unit_cost) }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ formatIqd(b.value) }}</td>
                        <td class="px-4 py-2.5">
                            <span v-if="b.expiry_date" class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="expiryClasses[b.expiry_status] || 'text-grey-text'">
                                {{ b.expiry_date }}
                            </span>
                            <span v-else class="text-grey-text">—</span>
                        </td>
                    </tr>
                    <tr v-if="batches.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-grey-text">لا توجد دفعات مسجلة لهذا المنتج بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <a :href="route('inventory.index')" class="utu-btn-ghost mt-4 inline-block">رجوع</a>
    </AppLayout>
</template>
