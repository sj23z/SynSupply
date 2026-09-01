<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ customers: Array, products: Array, reps: Array });

const form = useForm({
    customer_id: '',
    sales_rep_id: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    language: 'ar',
    invoice_discount_type: '',
    invoice_discount_value: 0,
    notes: '',
    items: [{ product_id: '', description: '', qty: 1, unit_price: 0, discount_type: '', discount_value: 0 }],
});

function addLine() {
    form.items.push({ product_id: '', description: '', qty: 1, unit_price: 0, discount_type: '', discount_value: 0 });
}
function removeLine(i) {
    if (form.items.length > 1) form.items.splice(i, 1);
}
function productSelected(i) {
    const p = props.products.find((p) => p.id === form.items[i].product_id);
    if (p) form.items[i].unit_price = p.selling_price;
}

function lineTotal(item) {
    const gross = item.qty * item.unit_price;
    const discount = item.discount_type === 'percent' ? Math.round(gross * item.discount_value / 100) : (item.discount_type === 'fixed' ? Math.min(item.discount_value, gross) : 0);
    return Math.max(0, gross - discount);
}
const subtotal = computed(() => form.items.reduce((s, i) => s + i.qty * i.unit_price, 0));
const itemDiscountTotal = computed(() => form.items.reduce((s, i) => s + (i.qty * i.unit_price - lineTotal(i)), 0));
const afterItemDiscounts = computed(() => subtotal.value - itemDiscountTotal.value);
const invoiceDiscountAmount = computed(() => {
    if (!form.invoice_discount_type || !form.invoice_discount_value) return 0;
    return form.invoice_discount_type === 'percent'
        ? Math.round(afterItemDiscounts.value * form.invoice_discount_value / 100)
        : Math.min(form.invoice_discount_value, afterItemDiscounts.value);
});
const grandTotal = computed(() => Math.max(0, afterItemDiscounts.value - invoiceDiscountAmount.value));

function formatIqd(v) {
    return new Intl.NumberFormat('en-US').format(v) + ' د.ع';
}

function submit() {
    form.post(route('invoices.store'));
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">فاتورة مبيعات جديدة</h1>

        <form @submit.prevent="submit">
            <div class="utu-card mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="utu-label">العميل</label>
                    <select v-model="form.customer_id" class="utu-input" required>
                        <option value="">اختر عميلاً</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <p v-if="form.errors.customer_id" class="mt-1 text-[12px] text-danger">{{ form.errors.customer_id }}</p>
                </div>
                <div>
                    <label class="utu-label">تاريخ الفاتورة</label>
                    <input v-model="form.invoice_date" type="date" class="utu-input" required>
                </div>
                <div>
                    <label class="utu-label">المندوب — اختياري</label>
                    <select v-model="form.sales_rep_id" class="utu-input">
                        <option value="">—</option>
                        <option v-for="r in reps" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="utu-label">لغة الفاتورة</label>
                    <select v-model="form.language" class="utu-input">
                        <option value="ar">العربية</option>
                        <option value="en">English</option>
                    </select>
                </div>
            </div>

            <div class="utu-card mb-4 overflow-hidden !p-0">
                <!-- Desktop/tablet: existing horizontal table, unchanged. -->
                <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-[13px]">
                    <thead class="bg-grey-1 text-[12px] text-grey-text">
                        <tr>
                            <th class="px-3 py-2 text-start">المنتج</th>
                            <th class="px-3 py-2 text-start w-20">الكمية</th>
                            <th class="px-3 py-2 text-start w-32">السعر</th>
                            <th class="px-3 py-2 text-start w-24">نوع الخصم</th>
                            <th class="px-3 py-2 text-start w-24">قيمة الخصم</th>
                            <th class="px-3 py-2 text-start w-32">الإجمالي</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, i) in form.items" :key="i" class="border-t border-grey-2">
                            <td class="px-3 py-2">
                                <select v-model="item.product_id" @change="productSelected(i)" class="utu-input" required>
                                    <option value="">اختر منتجاً</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input v-model.number="item.qty" type="number" min="1" class="utu-input"></td>
                            <td class="px-3 py-2"><input v-model.number="item.unit_price" type="number" min="0" class="utu-input"></td>
                            <td class="px-3 py-2">
                                <select v-model="item.discount_type" class="utu-input">
                                    <option value="">بدون</option>
                                    <option value="percent">%</option>
                                    <option value="fixed">مبلغ</option>
                                </select>
                            </td>
                            <td class="px-3 py-2"><input v-model.number="item.discount_value" type="number" min="0" class="utu-input"></td>
                            <td class="px-3 py-2 font-medium">{{ formatIqd(lineTotal(item)) }}</td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" class="text-danger" @click="removeLine(i)">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>

                <!-- Mobile: vertically stacked card per item — the horizontal table is
                     unusable at 360-430px (7 columns, each field compressed to a sliver),
                     so below md: each invoice line becomes its own full-width card with
                     one labeled field per row, matching the existing Collection form's
                     mobile pattern. Same v-model bindings, same lineTotal()/productSelected()
                     functions, same form.items array — this is presentation-only. -->
                <div class="divide-y divide-grey-2 md:hidden">
                    <div v-for="(item, i) in form.items" :key="i" class="space-y-3 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] font-semibold text-grey-text">السطر {{ i + 1 }}</span>
                            <button type="button" class="utu-btn-ghost !border-danger/30 !px-2.5 !py-1 !text-[12px] !text-danger" @click="removeLine(i)">✕ حذف</button>
                        </div>
                        <div>
                            <label class="utu-label">المنتج</label>
                            <select v-model="item.product_id" @change="productSelected(i)" class="utu-input" required>
                                <option value="">اختر منتجاً</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="utu-label">الكمية</label>
                            <input v-model.number="item.qty" type="number" min="1" class="utu-input">
                        </div>
                        <div>
                            <label class="utu-label">السعر</label>
                            <input v-model.number="item.unit_price" type="number" min="0" class="utu-input">
                        </div>
                        <div>
                            <label class="utu-label">نوع الخصم</label>
                            <select v-model="item.discount_type" class="utu-input">
                                <option value="">بدون</option>
                                <option value="percent">%</option>
                                <option value="fixed">مبلغ</option>
                            </select>
                        </div>
                        <div>
                            <label class="utu-label">قيمة الخصم</label>
                            <input v-model.number="item.discount_value" type="number" min="0" class="utu-input">
                        </div>
                        <div class="flex items-center justify-between rounded-utu bg-grey-1 px-3 py-2.5">
                            <span class="text-[12px] text-grey-text">الإجمالي</span>
                            <span class="font-semibold">{{ formatIqd(lineTotal(item)) }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <button type="button" class="utu-btn-ghost w-full !text-[12px] md:w-auto" @click="addLine">+ إضافة سطر</button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="utu-card space-y-3">
                    <div>
                        <label class="utu-label">ملاحظات — اختياري</label>
                        <textarea v-model="form.notes" class="utu-input" rows="3"></textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="utu-label">نوع خصم الفاتورة</label>
                            <select v-model="form.invoice_discount_type" class="utu-input">
                                <option value="">بدون</option>
                                <option value="percent">%</option>
                                <option value="fixed">مبلغ</option>
                            </select>
                        </div>
                        <div>
                            <label class="utu-label">قيمة الخصم</label>
                            <input v-model.number="form.invoice_discount_value" type="number" min="0" class="utu-input">
                        </div>
                    </div>
                </div>

                <div class="utu-card">
                    <table class="w-full text-[13px]">
                        <tr><td class="py-1 text-grey-text">المجموع الفرعي</td><td class="py-1 text-end">{{ formatIqd(subtotal) }}</td></tr>
                        <tr><td class="py-1 text-grey-text">خصم العناصر</td><td class="py-1 text-end">{{ formatIqd(itemDiscountTotal) }}</td></tr>
                        <tr><td class="py-1 text-grey-text">خصم الفاتورة</td><td class="py-1 text-end">{{ formatIqd(invoiceDiscountAmount) }}</td></tr>
                        <tr class="border-t border-grey-2 font-bold"><td class="py-2">الإجمالي</td><td class="py-2 text-end">{{ formatIqd(grandTotal) }}</td></tr>
                    </table>
                    <div class="mt-3 flex gap-2">
                        <button type="submit" class="utu-btn-dark flex-1" :disabled="form.processing">حفظ كمسودة</button>
                        <a :href="route('invoices.index')" class="utu-btn-ghost">إلغاء</a>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
