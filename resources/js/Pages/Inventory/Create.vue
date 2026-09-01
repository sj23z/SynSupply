<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ products: Array });

const form = useForm({
    product_id: '',
    quantity: 1,
    unit_cost: 0,
    entry_date: new Date().toISOString().slice(0, 10),
    batch_number: '',
    expiry_date: '',
    notes: '',
});

function submit() {
    form.post(route('inventory.store'));
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">إدخال مخزون جديد</h1>

        <form class="utu-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <label class="utu-label">المنتج</label>
                <select v-model="form.product_id" class="utu-input" required>
                    <option value="">اختر منتجاً</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} <template v-if="p.sku">({{ p.sku }})</template></option>
                </select>
                <p v-if="form.errors.product_id" class="mt-1 text-[12px] text-danger">{{ form.errors.product_id }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="utu-label">الكمية</label>
                    <input v-model.number="form.quantity" type="number" min="1" class="utu-input" required>
                    <p v-if="form.errors.quantity" class="mt-1 text-[12px] text-danger">{{ form.errors.quantity }}</p>
                </div>
                <div>
                    <label class="utu-label">تكلفة الشراء للوحدة (د.ع)</label>
                    <input v-model.number="form.unit_cost" type="number" min="0" class="utu-input" required>
                    <p v-if="form.errors.unit_cost" class="mt-1 text-[12px] text-danger">{{ form.errors.unit_cost }}</p>
                </div>
            </div>

            <div>
                <label class="utu-label">تاريخ الإدخال</label>
                <input v-model="form.entry_date" type="date" class="utu-input" required>
                <p v-if="form.errors.entry_date" class="mt-1 text-[12px] text-danger">{{ form.errors.entry_date }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="utu-label">رقم الدفعة — اختياري</label>
                    <input v-model="form.batch_number" type="text" class="utu-input">
                </div>
                <div>
                    <label class="utu-label">تاريخ انتهاء الصلاحية — اختياري</label>
                    <input v-model="form.expiry_date" type="date" class="utu-input">
                </div>
            </div>

            <div>
                <label class="utu-label">ملاحظات — اختياري</label>
                <textarea v-model="form.notes" class="utu-input" rows="3"></textarea>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ</button>
                <a :href="route('inventory.index')" class="utu-btn-ghost">إلغاء</a>
            </div>
        </form>
    </AppLayout>
</template>
