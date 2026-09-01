<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    areas: Array,
    filters: Object,
});

const page = usePage();
const isAdmin = page.props.auth.user.role === 'admin';

const search = ref(props.filters.search ?? '');
function runSearch() {
    router.get(route('areas.index'), { search: search.value }, { preserveState: true, replace: true });
}

const createForm = useForm({ name: '', active: true });
function submitCreate() {
    createForm.post(route('areas.store'), { onSuccess: () => createForm.reset() });
}

const editingId = ref(null);
const editForm = useForm({ name: '', active: true });
function startEdit(area) {
    editingId.value = area.id;
    editForm.name = area.name;
    editForm.active = area.active;
}
function submitEdit(area) {
    editForm.put(route('areas.update', area.id), { onSuccess: () => (editingId.value = null) });
}

function destroy(area) {
    if (confirm(`حذف المنطقة "${area.name}"؟`)) {
        router.delete(route('areas.destroy', area.id));
    }
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">المناطق</h1>
            <input v-model="search" @input="runSearch" type="text" placeholder="بحث بالاسم…" class="utu-input w-56">
        </div>

        <div v-if="isAdmin" class="utu-card mb-4">
            <form class="flex items-end gap-3" @submit.prevent="submitCreate">
                <div class="flex-1">
                    <label class="utu-label">منطقة جديدة</label>
                    <input v-model="createForm.name" type="text" class="utu-input" placeholder="اسم المنطقة">
                    <p v-if="createForm.errors.name" class="mt-1 text-[12px] text-danger">{{ createForm.errors.name }}</p>
                </div>
                <button type="submit" class="utu-btn-gold" :disabled="createForm.processing">إضافة</button>
            </form>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">الاسم</th>
                        <th class="px-4 py-2.5 text-start font-semibold">المندوبين</th>
                        <th class="px-4 py-2.5 text-start font-semibold">العملاء</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الحالة</th>
                        <th v-if="isAdmin" class="px-4 py-2.5 text-start font-semibold"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="area in areas" :key="area.id" class="border-t border-grey-2">
                        <template v-if="editingId === area.id">
                            <td class="px-4 py-2" colspan="3">
                                <input v-model="editForm.name" type="text" class="utu-input">
                            </td>
                            <td class="px-4 py-2">
                                <label class="flex items-center gap-1.5 text-[12px]">
                                    <input v-model="editForm.active" type="checkbox"> فعّالة
                                </label>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-end">
                                <button class="utu-btn-gold !px-3 !py-1.5" @click="submitEdit(area)">حفظ</button>
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="editingId = null">إلغاء</button>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-2.5 font-medium">{{ area.name }}</td>
                            <td class="px-4 py-2.5 text-grey-text">{{ area.sales_representatives_count }}</td>
                            <td class="px-4 py-2.5 text-grey-text">{{ area.customers_count }}</td>
                            <td class="px-4 py-2.5">
                                <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="area.active ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">
                                    {{ area.active ? 'فعّالة' : 'غير فعّالة' }}
                                </span>
                            </td>
                            <td v-if="isAdmin" class="whitespace-nowrap px-4 py-2.5 text-end">
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="startEdit(area)">تعديل</button>
                                <button class="utu-btn-ghost !border-danger/30 !px-3 !py-1.5 !text-danger" @click="destroy(area)">حذف</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="areas.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-grey-text">لا توجد مناطق بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
