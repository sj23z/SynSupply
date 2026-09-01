<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    reps: Array,
    areas: Array,
    filters: Object,
});

const page = usePage();
const isAdmin = page.props.auth.user.role === 'admin';

const search = ref(props.filters.search ?? '');
const areaFilter = ref(props.filters.area_id ?? '');
function runFilter() {
    router.get(route('sales-representatives.index'), { search: search.value, area_id: areaFilter.value }, { preserveState: true, replace: true });
}

const createForm = useForm({ name: '', phone: '', area_id: '', active: true });
function submitCreate() {
    createForm.post(route('sales-representatives.store'), { onSuccess: () => createForm.reset() });
}

const editingId = ref(null);
const editForm = useForm({ name: '', phone: '', area_id: '', active: true });
function startEdit(rep) {
    editingId.value = rep.id;
    editForm.name = rep.name;
    editForm.phone = rep.phone;
    editForm.area_id = rep.area_id ?? '';
    editForm.active = rep.active;
}
function submitEdit(rep) {
    editForm.put(route('sales-representatives.update', rep.id), { onSuccess: () => (editingId.value = null) });
}
function destroy(rep) {
    if (confirm(`حذف المندوب "${rep.name}"؟`)) {
        router.delete(route('sales-representatives.destroy', rep.id));
    }
}

const grantingId = ref(null);
const grantForm = useForm({ email: '', password: '' });
function startGrant(rep) {
    grantingId.value = rep.id;
    grantForm.reset();
}
function submitGrant(rep) {
    grantForm.post(route('sales-representatives.grant-access', rep.id), { onSuccess: () => (grantingId.value = null) });
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">مناديب المبيعات</h1>
            <div class="flex gap-2">
                <select v-model="areaFilter" @change="runFilter" class="utu-input w-40">
                    <option value="">كل المناطق</option>
                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
                <input v-model="search" @input="runFilter" type="text" placeholder="بحث بالاسم…" class="utu-input w-52">
            </div>
        </div>

        <div v-if="isAdmin" class="utu-card mb-4">
            <form class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submitCreate">
                <div>
                    <label class="utu-label">الاسم</label>
                    <input v-model="createForm.name" type="text" class="utu-input">
                    <p v-if="createForm.errors.name" class="mt-1 text-[12px] text-danger">{{ createForm.errors.name }}</p>
                </div>
                <div>
                    <label class="utu-label">الهاتف (اختياري)</label>
                    <input v-model="createForm.phone" type="text" class="utu-input">
                </div>
                <div>
                    <label class="utu-label">المنطقة (اختياري)</label>
                    <select v-model="createForm.area_id" class="utu-input">
                        <option value="">—</option>
                        <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                </div>
                <button type="submit" class="utu-btn-gold" :disabled="createForm.processing">إضافة مندوب</button>
            </form>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">الاسم</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الهاتف</th>
                        <th class="px-4 py-2.5 text-start font-semibold">المنطقة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">عدد العملاء</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الحالة</th>
                        <th class="px-4 py-2.5 text-start font-semibold">دخول النظام</th>
                        <th v-if="isAdmin" class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rep in reps" :key="rep.id" class="border-t border-grey-2">
                        <template v-if="editingId === rep.id">
                            <td class="px-4 py-2"><input v-model="editForm.name" type="text" class="utu-input"></td>
                            <td class="px-4 py-2"><input v-model="editForm.phone" type="text" class="utu-input"></td>
                            <td class="px-4 py-2">
                                <select v-model="editForm.area_id" class="utu-input">
                                    <option value="">—</option>
                                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 text-grey-text">{{ rep.customers_count }}</td>
                            <td class="px-4 py-2">
                                <label class="flex items-center gap-1.5 text-[12px]">
                                    <input v-model="editForm.active" type="checkbox"> فعّال
                                </label>
                            </td>
                            <td class="px-4 py-2 text-grey-text">—</td>
                            <td class="whitespace-nowrap px-4 py-2 text-end">
                                <button class="utu-btn-gold !px-3 !py-1.5" @click="submitEdit(rep)">حفظ</button>
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="editingId = null">إلغاء</button>
                            </td>
                        </template>
                        <template v-else>
                            <td class="px-4 py-2.5 font-medium">{{ rep.name }}</td>
                            <td class="px-4 py-2.5 text-grey-text">{{ rep.phone || '—' }}</td>
                            <td class="px-4 py-2.5 text-grey-text">{{ rep.area?.name || '—' }}</td>
                            <td class="px-4 py-2.5 text-grey-text">{{ rep.customers_count }}</td>
                            <td class="px-4 py-2.5">
                                <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="rep.active ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">
                                    {{ rep.active ? 'فعّال' : 'غير فعّال' }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5">
                                <span v-if="rep.user_id" class="rounded-utu bg-gold-soft/40 px-2 py-0.5 text-[11px] font-semibold text-ink">مفعّل</span>
                                <template v-else-if="isAdmin">
                                    <button v-if="grantingId !== rep.id" class="utu-btn-ghost !px-2 !py-1 !text-[11px]" @click="startGrant(rep)">تفعيل دخول</button>
                                    <form v-else class="flex flex-col gap-1" @submit.prevent="submitGrant(rep)">
                                        <input v-model="grantForm.email" type="email" placeholder="البريد الإلكتروني" class="utu-input !py-1 !text-[11px]" required>
                                        <input v-model="grantForm.password" type="password" placeholder="كلمة المرور" class="utu-input !py-1 !text-[11px]" required>
                                        <div class="flex gap-1">
                                            <button type="submit" class="utu-btn-gold !px-2 !py-1 !text-[11px]">تأكيد</button>
                                            <button type="button" class="utu-btn-ghost !px-2 !py-1 !text-[11px]" @click="grantingId = null">إلغاء</button>
                                        </div>
                                    </form>
                                </template>
                                <span v-else class="text-grey-text">—</span>
                            </td>
                            <td v-if="isAdmin" class="whitespace-nowrap px-4 py-2.5 text-end">
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="startEdit(rep)">تعديل</button>
                                <button class="utu-btn-ghost !border-danger/30 !px-3 !py-1.5 !text-danger" @click="destroy(rep)">حذف</button>
                            </td>
                        </template>
                    </tr>
                    <tr v-if="reps.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-grey-text">لا يوجد مناديب بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
