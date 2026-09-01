<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ users: Array });

const roleLabels = { admin: 'مدير', staff: 'موظف', sales_rep: 'مندوب مبيعات', owner: 'مالك' };

const createForm = useForm({ name: '', email: '', password: '', password_confirmation: '', role: 'staff' });
function submitCreate() {
    createForm.post(route('users.store'), { onSuccess: () => createForm.reset() });
}

function toggleActive(u) {
    if (confirm(`${u.active ? 'إلغاء تفعيل' : 'تفعيل'} حساب "${u.name}"؟`)) {
        router.patch(route('users.toggle-active', u.id));
    }
}

const resettingId = ref(null);
const resetForm = useForm({ password: '', password_confirmation: '' });
function startReset(u) {
    resettingId.value = u.id;
    resetForm.reset();
}
function submitReset(u) {
    resetForm.put(route('users.reset-password', u.id), { onSuccess: () => (resettingId.value = null) });
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <h1 class="mb-4 text-base font-bold text-ink">المستخدمون</h1>

        <div class="utu-card mb-4">
            <form class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="submitCreate">
                <div>
                    <label class="utu-label">الاسم</label>
                    <input v-model="createForm.name" type="text" class="utu-input">
                    <p v-if="createForm.errors.name" class="mt-1 text-[12px] text-danger">{{ createForm.errors.name }}</p>
                </div>
                <div>
                    <label class="utu-label">البريد الإلكتروني</label>
                    <input v-model="createForm.email" type="email" class="utu-input">
                    <p v-if="createForm.errors.email" class="mt-1 text-[12px] text-danger">{{ createForm.errors.email }}</p>
                </div>
                <div>
                    <label class="utu-label">كلمة المرور</label>
                    <input v-model="createForm.password" type="password" class="utu-input">
                    <p v-if="createForm.errors.password" class="mt-1 text-[12px] text-danger">{{ createForm.errors.password }}</p>
                </div>
                <div>
                    <label class="utu-label">تأكيد كلمة المرور</label>
                    <input v-model="createForm.password_confirmation" type="password" class="utu-input">
                </div>
                <div class="flex flex-wrap items-end gap-2">
                    <div class="flex-1">
                        <label class="utu-label">الدور</label>
                        <select v-model="createForm.role" class="utu-input">
                            <option value="staff">موظف</option>
                            <option value="admin">مدير</option>
                            <option value="owner">مالك</option>
                        </select>
                    </div>
                    <button type="submit" class="utu-btn-gold" :disabled="createForm.processing">إضافة</button>
                </div>
            </form>
            <p class="mt-2 text-[11px] text-grey-text">
                لإنشاء حساب دخول لمندوب مبيعات، استخدم زر "تفعيل دخول" من صفحة المندوبين.
            </p>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start">الاسم</th>
                        <th class="px-4 py-2.5 text-start">البريد الإلكتروني</th>
                        <th class="px-4 py-2.5 text-start">الدور</th>
                        <th class="px-4 py-2.5 text-start">الحالة</th>
                        <th class="px-4 py-2.5 text-start">آخر دخول</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ u.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ u.email }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ roleLabels[u.role] }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="u.active ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">
                                {{ u.active ? 'فعّال' : 'غير فعّال' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-grey-text">{{ u.last_login_at || '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <template v-if="resettingId === u.id">
                                <form class="flex flex-col items-end gap-1" @submit.prevent="submitReset(u)">
                                    <div class="flex items-center gap-1">
                                        <input v-model="resetForm.password" type="password" placeholder="كلمة مرور جديدة" class="utu-input !w-36 !py-1 !text-[11px]">
                                        <input v-model="resetForm.password_confirmation" type="password" placeholder="تأكيد" class="utu-input !w-28 !py-1 !text-[11px]">
                                        <button type="submit" class="utu-btn-gold !px-2 !py-1 !text-[11px]">حفظ</button>
                                        <button type="button" class="utu-btn-ghost !px-2 !py-1 !text-[11px]" @click="resettingId = null">إلغاء</button>
                                    </div>
                                    <p v-if="resetForm.errors.password" class="text-[11px] text-danger">{{ resetForm.errors.password }}</p>
                                </form>
                            </template>
                            <template v-else>
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="startReset(u)">إعادة تعيين كلمة المرور</button>
                                <button class="utu-btn-ghost !px-3 !py-1.5" @click="toggleActive(u)">
                                    {{ u.active ? 'إلغاء التفعيل' : 'تفعيل' }}
                                </button>
                            </template>
                        </td>
                    </tr>
                    <tr v-if="users.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-grey-text">لا يوجد مستخدمون بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
