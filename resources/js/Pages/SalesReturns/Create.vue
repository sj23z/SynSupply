<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ customers: Array, products: Array, reps: Array, prefillInvoiceId: Number });

const form = useForm({
    customer_id: '',
    invoice_id: props.prefillInvoiceId || '',
    sales_rep_id: '',
    return_date: new Date().toISOString().slice(0, 10),
    reason: '',
    notes: '',
    items: [{ product_id: '', invoice_item_id: null, qty: 1, unit_price: 0, batch_number: '', expiry_date: '' }],
});

function addLine() {
    form.items.push({ product_id: '', invoice_item_id: null, qty: 1, unit_price: 0, batch_number: '', expiry_date: '' });
}
function removeLine(i) {
    if (form.items.length > 1) form.items.splice(i, 1);
}
function productSelected(i) {
    const p = props.products.find((p) => p.id === form.items[i].product_id);
    if (p) form.items[i].unit_price = p.selling_price;
}

async function loadFromInvoice() {
    if (!form.invoice_id) return;
    const res = await fetch(route('invoices.items-for-return', form.invoice_id));
    if (!res.ok) return;
    const lines = await res.json();
    form.items = lines.map((l) => ({ product_id: l.product_id, invoice_item_id: l.id, qty: l.qty, unit_price: l.unit_price, batch_number: '', expiry_date: '' }));
}

const total = computed(() => form.items.reduce((s, i) => s + i.qty * i.unit_price, 0));
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
function submit() { form.post(route('sales-returns.store')); }
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">مردود مبيعات جديد</h1>

        <form @submit.prevent="submit">
            <div class="utu-card mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="utu-label">العميل</label>
                    <select v-model="form.customer_id" class="utu-input" required>
                        <option value="">اختر عميلاً</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="utu-label">تاريخ المردود</label>
                    <input v-model="form.return_date" type="date" class="utu-input" required>
                </div>
                <div>
                    <label class="utu-label">رقم الفاتورة الأصلية — اختياري</label>
                    <div class="flex gap-1">
                        <input v-model.number="form.invoice_id" type="number" class="utu-input" placeholder="رقم الفاتورة">
                        <button type="button" class="utu-btn-ghost !px-2 !text-[11px]" @click="loadFromInvoice">تحميل</button>
                    </div>
                </div>
                <div>
                    <label class="utu-label">السبب — اختياري</label>
                    <input v-model="form.reason" type="text" class="utu-input">
                </div>
            </div>

            <div class="utu-card mb-4 overflow-hidden !p-0">
                <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                    <thead class="bg-grey-1 text-[12px] text-grey-text">
                        <tr>
                            <th class="px-3 py-2 text-start">المنتج</th>
                            <th class="px-3 py-2 text-start w-20">الكمية</th>
                            <th class="px-3 py-2 text-start w-32">السعر</th>
                            <th class="px-3 py-2 text-start w-28">رقم الدفعة (اختياري)</th>
                            <th class="px-3 py-2 text-start w-36">انتهاء الصلاحية (اختياري)</th>
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
                            <td class="px-3 py-2"><input v-model="item.batch_number" type="text" class="utu-input"></td>
                            <td class="px-3 py-2"><input v-model="item.expiry_date" type="date" class="utu-input"></td>
                            <td class="px-3 py-2 font-medium">{{ formatIqd(item.qty * item.unit_price) }}</td>
                            <td class="px-3 py-2 text-center"><button type="button" class="text-danger" @click="removeLine(i)">✕</button></td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <div class="p-3"><button type="button" class="utu-btn-ghost !text-[12px]" @click="addLine">+ إضافة سطر</button></div>
            </div>

            <div class="utu-card flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <label class="utu-label">ملاحظات — اختياري</label>
                    <textarea v-model="form.notes" class="utu-input w-full sm:w-96" rows="2"></textarea>
                </div>
                <div class="text-end">
                    <p class="text-[12px] text-grey-text">إجمالي المردود</p>
                    <p class="text-lg font-bold">{{ formatIqd(total) }}</p>
                    <div class="mt-2 flex gap-2">
                        <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ كمسودة</button>
                        <a :href="route('sales-returns.index')" class="utu-btn-ghost">إلغاء</a>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
