<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    customer: Object,
    areas: Array,
    reps: Array,
    canChangeActiveStatus: Boolean,
    canReassign: Boolean,
});

const form = useForm({
    name: props.customer.name,
    code: props.customer.code,
    phone: props.customer.phone,
    area_id: props.customer.area_id ?? '',
    address: props.customer.address,
    assigned_rep_id: props.customer.assigned_rep_id ?? '',
    notes: props.customer.notes,
    is_special_case: props.customer.is_special_case,
});

function submit() {
    form.put(route('customers.update', props.customer.id));
}

function toggleActive() {
    router.patch(route('customers.toggle-active', props.customer.id));
}

const visitForm = useForm({ visit_date: new Date().toISOString().slice(0, 10), notes: '', follow_up_date: '' });
function submitVisit() {
    visitForm.post(route('customer-visits.store', props.customer.id), { onSuccess: () => visitForm.reset() });
}

function completeFollowUp(visitId) {
    router.patch(route('customer-visits.complete-follow-up', visitId));
}
</script>

<template>
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h1 class="text-base font-bold text-ink">تعديل بيانات العميل</h1>
            <div class="flex gap-2">
                <a :href="route('customers.statement', customer.id)" class="utu-btn-ghost">كشف حساب</a>
                <button v-if="canChangeActiveStatus" type="button" class="utu-btn-ghost" @click="toggleActive">
                    {{ customer.active ? 'إلغاء تفعيل العميل' : 'تفعيل العميل' }}
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <form class="utu-card col-span-2 space-y-4" @submit.prevent="submit">
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

                <div>
                    <label class="utu-label">
                        المندوب المسؤول
                        <span v-if="!canReassign" class="font-normal text-grey-text">(لا يمكن تغييره إلا من قبل المدير)</span>
                    </label>
                    <select v-model="form.assigned_rep_id" class="utu-input" :disabled="!canReassign">
                        <option value="">—</option>
                        <option v-for="r in reps" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="utu-label">ملاحظات — اختياري</label>
                    <textarea v-model="form.notes" class="utu-input" rows="3"></textarea>
                </div>

                <label class="flex items-center gap-2 text-[12.5px] text-grey-text">
                    <input v-model="form.is_special_case" type="checkbox" class="rounded-utu border-grey-3">
                    عميل ذو حالة خاصة (مثل: تالف)
                </label>

                <div class="flex gap-2">
                    <button type="submit" class="utu-btn-dark" :disabled="form.processing">حفظ التعديلات</button>
                    <a :href="route('customers.index')" class="utu-btn-ghost">إلغاء</a>
                </div>
            </form>

            <!-- Visit History: the "operational memory" of the customer. -->
            <div class="utu-card space-y-4">
                <h2 class="text-[13px] font-bold text-ink">سجل الزيارات</h2>

                <form class="space-y-2 border-b border-grey-2 pb-4" @submit.prevent="submitVisit">
                    <input v-model="visitForm.visit_date" type="date" class="utu-input" required>
                    <textarea v-model="visitForm.notes" class="utu-input" rows="2" placeholder="ملاحظات الزيارة…" required></textarea>
                    <input v-model="visitForm.follow_up_date" type="date" class="utu-input" placeholder="تاريخ المتابعة — اختياري">
                    <button type="submit" class="utu-btn-gold w-full !py-1.5 !text-[12px]" :disabled="visitForm.processing">تسجيل زيارة</button>
                </form>

                <ul class="max-h-80 space-y-2 overflow-y-auto">
                    <li v-for="v in customer.visits" :key="v.id" class="rounded-utu bg-grey-1 px-3 py-2 text-[12px]">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-ink">{{ v.visit_date }}</span>
                            <span v-if="v.follow_up_status === 'pending'" class="rounded-utu bg-gold-soft/40 px-1.5 py-0.5 text-[10px] text-ink">
                                متابعة: {{ v.follow_up_date }}
                            </span>
                        </div>
                        <p class="mt-1 text-grey-text">{{ v.notes }}</p>
                        <button
                            v-if="v.follow_up_status === 'pending'"
                            class="mt-1 text-[11px] font-semibold text-ink underline"
                            @click="completeFollowUp(v.id)"
                        >
                            تحديد المتابعة كمكتملة
                        </button>
                    </li>
                    <li v-if="!customer.visits?.length" class="text-[12px] text-grey-text">لا توجد زيارات مسجلة بعد.</li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
