<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ payment: Object, customers: Array, openInvoices: Array });

const form = useForm({
    customer_id: props.payment.customer_id,
    invoice_id: props.payment.invoice_id || '',
    payment_date: props.payment.payment_date,
    amount: props.payment.amount,
    method: props.payment.method,
    notes: props.payment.notes || '',
});

function submit() { form.put(route('collections.update', props.payment.id)); }
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">تعديل دفعة — {{ payment.customer?.name }}</h1>

        <form class="utu-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <label class="utu-label">العميل</label>
                <select v-model="form.customer_id" class="utu-input" required>
                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>

            <div>
                <label class="utu-label">ربط بفاتورة — اختياري</label>
                <select v-model="form.invoice_id" class="utu-input">
                    <option value="">—</option>
                    <option v-for="i in openInvoices" :key="i.id" :value="i.id">{{ i.invoice_number }} ({{ formatIqd(i.grand_total - i.amount_paid_cached) }} متبقي)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="utu-label">تاريخ الدفعة</label>
                    <input v-model="form.payment_date" type="date" class="utu-input" required>
                </div>
                <div>
                    <label class="utu-label">المبلغ (د.ع)</label>
                    <input v-model.number="form.amount" type="number" min="1" class="utu-input" required>
                    <p v-if="form.errors.amount" class="mt-1 text-[12px] text-danger">{{ form.errors.amount }}</p>
                </div>
            </div>

            <div>
                <label class="utu-label">طريقة الدفع</label>
                <select v-model="form.method" class="utu-input">
                    <option value="cash">نقدي</option>
                    <option value="bank_transfer">حوالة بنكية</option>
                    <option value="other">أخرى</option>
                    <option value="settlement">تسوية</option>
                    <option value="discount">خصم</option>
                </select>
                <p class="mt-1 text-[11px] text-grey-text">التسوية والخصم يخفضان رصيد العميل المستحق ولا يُحتسبان ضمن النقد الفعلي في الصندوق. تغيير الطريقة يُحدّث تأثيرها على النقد فوراً.</p>
            </div>

            <div>
                <label class="utu-label">ملاحظات — اختياري</label>
                <textarea v-model="form.notes" class="utu-input" rows="2"></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ التعديلات</button>
                <a :href="route('collections.index')" class="utu-btn-ghost">إلغاء</a>
            </div>
        </form>
    </AppLayout>
</template>
