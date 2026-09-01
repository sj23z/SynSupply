<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ expenses: Object, categories: Array, filters: Object, totalForFilter: Number });

const categoryFilter = ref(props.filters.category_id ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
function runFilter() {
    router.get(route('expenses.index'), { category_id: categoryFilter.value, from: from.value, to: to.value }, { preserveState: true, replace: true });
}
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
function destroy(e) {
    if (confirm('حذف هذا المصروف؟')) router.delete(route('expenses.destroy', e.id));
}

const catForm = useForm({ name: '', active: true });
function addCategory() {
    catForm.post(route('expenses.categories.store'), { onSuccess: () => catForm.reset() });
}
</script>

<template>
    <AppLayout>
        <FlashBanner />
        <div class="mb-4 flex items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">المصروفات</h1>
            <Link :href="route('expenses.create')" class="utu-btn-gold">مصروف جديد</Link>
        </div>

        <div class="utu-card mb-4 flex flex-wrap items-end gap-2">
            <div>
                <label class="utu-label">التصنيف</label>
                <select v-model="categoryFilter" @change="runFilter" class="utu-input w-44">
                    <option value="">كل التصنيفات</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <div><label class="utu-label">من</label><input v-model="from" @change="runFilter" type="date" class="utu-input"></div>
            <div><label class="utu-label">إلى</label><input v-model="to" @change="runFilter" type="date" class="utu-input"></div>
            <div class="ms-auto text-end">
                <p class="text-[12px] text-grey-text">الإجمالي</p>
                <p class="text-lg font-bold">{{ formatIqd(totalForFilter) }}</p>
            </div>
        </div>

        <div class="utu-card mb-4">
            <form class="flex flex-wrap items-end gap-2" @submit.prevent="addCategory">
                <div class="flex-1">
                    <label class="utu-label">تصنيف جديد</label>
                    <input v-model="catForm.name" type="text" class="utu-input" placeholder="مثال: رواتب، نقل، تسويق">
                </div>
                <button type="submit" class="utu-btn-ghost">إضافة تصنيف</button>
            </form>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-4 py-2.5 text-start">التاريخ</th><th class="px-4 py-2.5 text-start">التصنيف</th><th class="px-4 py-2.5 text-start">الوصف</th><th class="px-4 py-2.5 text-start">المبلغ</th><th class="px-4 py-2.5"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="e in expenses.data" :key="e.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5">{{ e.date }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ e.category?.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ e.description || '—' }}</td>
                        <td class="px-4 py-2.5">{{ formatIqd(e.amount) }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <button class="utu-btn-ghost !border-danger/30 !px-3 !py-1.5 !text-danger" @click="destroy(e)">حذف</button>
                        </td>
                    </tr>
                    <tr v-if="expenses.data.length === 0"><td colspan="5" class="px-4 py-8 text-center text-grey-text">لا توجد مصروفات مسجلة بعد.</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
