<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ areas: Array, reps: Array, isSalesRep: Boolean });

const form = useForm({
    name: '',
    code: '',
    phone: '',
    area_id: '',
    address: '',
    assigned_rep_id: '',
    notes: '',
    is_special_case: false,
});

function submit() {
    form.post(route('customers.store'));
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">عميل جديد</h1>

        <form class="utu-card max-w-2xl space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="utu-label">اسم العميل / العيادة</label>
                    <input v-model="form.name" type="text" class="utu-input" required>
                    <p v-if="form.errors.name" class="mt-1 text-[12px] text-danger">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="utu-label">رمز العميل — اختياري</label>
                    <input v-model="form.code" type="text" class="utu-input">
                    <p v-if="form.errors.code" class="mt-1 text-[12px] text-danger">{{ form.errors.code }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="utu-label">رقم الهاتف — اختياري</label>
                    <input v-model="form.phone" type="text" class="utu-input">
                </div>
                <div>
                    <label class="utu-label">المنطقة — اختياري</label>
                    <select v-model="form.area_id" class="utu-input">
                        <option value="">—</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="utu-label">العنوان — اختياري</label>
                <input v-model="form.address" type="text" class="utu-input">
            </div>

            <div v-if="!isSalesRep">
                <label class="utu-label">المندوب المسؤول — اختياري</label>
                <select v-model="form.assigned_rep_id" class="utu-input">
                    <option value="">—</option>
                    <option v-for="r in reps" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </div>
            <p v-else class="text-[12px] text-grey-text">سيتم ربط هذا العميل بك تلقائياً كمندوب مسؤول.</p>

            <div>
                <label class="utu-label">ملاحظات — اختياري</label>
                <textarea v-model="form.notes" class="utu-input" rows="3"></textarea>
            </div>

            <label class="flex items-center gap-2 text-[12.5px] text-grey-text">
                <input v-model="form.is_special_case" type="checkbox" class="rounded-utu border-grey-3">
                عميل ذو حالة خاصة (مثل: تالف)
            </label>

            <div class="flex gap-2">
                <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ</button>
                <a :href="route('customers.index')" class="utu-btn-ghost">إلغاء</a>
            </div>
        </form>
    </AppLayout>
</template>
