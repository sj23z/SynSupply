<script setup>
import { reactive } from 'vue';

const props = defineProps({
    lines: { type: Array, required: true },
    openingBalance: { type: Number, required: true },
    closingBalance: { type: Number, required: true },
    dense: { type: Boolean, default: false },
});

function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }

const typeLabels = { invoice: 'فاتورة', return: 'مردود', payment: 'دفعة', settlement: 'تسوية', discount: 'خصم' };

// Color coding scoped to this component only — red=invoice/charge,
// blue=payment, green=return, neutral=settlement/discount.
const typeRowClass = {
    invoice: 'border-r-2 border-r-danger',
    return: 'border-r-2 border-r-[#3E7A4F]',
    payment: 'border-r-2 border-r-[#2E5FA3]',
    settlement: 'border-r-2 border-r-grey-3',
    discount: 'border-r-2 border-r-grey-3',
};
const typeTextClass = {
    invoice: 'text-danger',
    return: 'text-[#3E7A4F]',
    payment: 'text-[#2E5FA3]',
    settlement: 'text-grey-text',
    discount: 'text-grey-text',
};

const expanded = reactive({});
function toggle(i) { expanded[i] = !expanded[i]; }
function discountText(item) {
    if (!item.discount_type || !item.discount_value) return '—';
    return item.discount_type === 'percent' ? `${item.discount_value}%` : formatIqd(item.discount_value);
}
</script>

<template>
    <div class="overflow-x-auto">
    <table class="w-full" :class="dense ? 'text-[12px]' : 'text-[13px]'">
        <thead class="bg-grey-1 text-[12px] text-grey-text">
            <tr><th class="px-3 py-2 text-start">التاريخ</th><th class="px-3 py-2 text-start">البيان</th><th class="px-3 py-2 text-start">مدين</th><th class="px-3 py-2 text-start">دائن</th><th class="px-3 py-2 text-start">الرصيد</th><th class="px-3 py-2"></th></tr>
        </thead>
        <tbody>
            <tr class="border-t border-grey-2 font-semibold bg-grey-1">
                <td class="px-3 py-2" colspan="4">الرصيد الافتتاحي</td>
                <td class="px-3 py-2">{{ formatIqd(openingBalance) }}</td>
                <td></td>
            </tr>
            <template v-for="(l, i) in lines" :key="i">
                <tr class="border-t border-grey-2" :class="typeRowClass[l.type]">
                    <td class="px-3 py-2">{{ l.date }}</td>
                    <td class="px-3 py-2">
                        <span class="font-medium" :class="typeTextClass[l.type]">{{ typeLabels[l.type] }}</span>
                        — {{ l.label }}
                    </td>
                    <td class="px-3 py-2">{{ l.debit ? formatIqd(l.debit) : '' }}</td>
                    <td class="px-3 py-2">{{ l.credit ? formatIqd(l.credit) : '' }}</td>
                    <td class="px-3 py-2">{{ formatIqd(l.running_balance) }}</td>
                    <td class="px-3 py-2 text-end">
                        <button v-if="l.detail?.items" class="text-[11px] text-grey-text underline" @click="toggle(i)">
                            {{ expanded[i] ? 'إخفاء' : 'تفاصيل' }}
                        </button>
                    </td>
                </tr>
                <tr v-if="expanded[i] && l.detail?.items" class="border-t border-grey-2 bg-grey-1/50">
                    <td colspan="6" class="px-3 py-2">
                        <table class="w-full text-[12px]">
                            <thead class="text-grey-text">
                                <tr v-if="l.type === 'invoice'">
                                    <th class="px-2 py-1 text-start">المنتج</th><th class="px-2 py-1 text-start">الكمية</th><th class="px-2 py-1 text-start">السعر</th><th class="px-2 py-1 text-start">الخصم</th><th class="px-2 py-1 text-start">الإجمالي</th>
                                </tr>
                                <tr v-else>
                                    <th class="px-2 py-1 text-start">المنتج</th><th class="px-2 py-1 text-start">الكمية</th><th class="px-2 py-1 text-start">القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, j) in l.detail.items" :key="j" class="border-t border-grey-2">
                                    <td class="px-2 py-1">{{ item.product_name }}</td>
                                    <td class="px-2 py-1">{{ item.qty }}</td>
                                    <template v-if="l.type === 'invoice'">
                                        <td class="px-2 py-1">{{ formatIqd(item.unit_price) }}</td>
                                        <td class="px-2 py-1">{{ discountText(item) }}</td>
                                        <td class="px-2 py-1 font-medium">{{ formatIqd(item.line_total) }}</td>
                                    </template>
                                    <template v-else>
                                        <td class="px-2 py-1 font-medium">{{ formatIqd(item.value) }}</td>
                                    </template>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </template>
            <tr v-if="lines.length === 0">
                <td colspan="6" class="px-3 py-6 text-center text-grey-text">لا توجد حركات لهذه الفترة.</td>
            </tr>
            <tr class="border-t border-grey-2 font-semibold bg-grey-1">
                <td class="px-3 py-2" colspan="4">الرصيد الختامي</td>
                <td class="px-3 py-2">{{ formatIqd(closingBalance) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    </div>
</template>
