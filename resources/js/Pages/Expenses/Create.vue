<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ categories: Array });

const form = useForm({ category_id: '', date: new Date().toISOString().slice(0, 10), description: '', amount: 0, notes: '' });
function submit() { form.post(route('expenses.store')); }
</script>

<template>
    <AppLayout>
        <h1 class="mb-4 text-base font-bold text-ink">مصروف جديد</h1>
        <form class="utu-card max-w-xl space-y-4" @submit.prevent="submit">
            <div>
                <label class="utu-label">التصنيف</label>
                <select v-model="form.category_id" class="utu-input" required>
                    <option value="">اختر تصنيفاً</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <p v-if="form.errors.category_id" class="mt-1 text-[12px] text-danger">{{ form.errors.category_id }}</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><label class="utu-label">التاريخ</label><input v-model="form.date" type="date" class="utu-input" required></div>
                <div><label class="utu-label">المبلغ (د.ع)</label><input v-model.number="form.amount" type="number" min="1" class="utu-input" required></div>
            </div>
            <div><label class="utu-label">الوصف — اختياري</label><input v-model="form.description" type="text" class="utu-input"></div>
            <div><label class="utu-label">ملاحظات — اختياري</label><textarea v-model="form.notes" class="utu-input" rows="2"></textarea></div>
            <div class="flex gap-2">
                <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ</button>
                <a :href="route('expenses.index')" class="utu-btn-ghost">إلغاء</a>
            </div>
        </form>
    </AppLayout>
</template>
