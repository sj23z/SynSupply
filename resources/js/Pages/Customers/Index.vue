<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    customers: Object,
    areas: Array,
    reps: Array,
    filters: Object,
    isSalesRep: Boolean,
});

const search = ref(props.filters.search ?? '');
const areaFilter = ref(props.filters.area_id ?? '');
const repFilter = ref(props.filters.assigned_rep_id ?? '');
const statusFilter = ref(props.filters.status ?? '');

function runFilter() {
    router.get(route('customers.index'), {
        search: search.value,
        area_id: areaFilter.value,
        assigned_rep_id: repFilter.value,
        status: statusFilter.value,
    }, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">{{ isSalesRep ? 'عملائي' : 'العملاء' }}</h1>
            <Link :href="route('customers.create')" class="utu-btn-gold">عميل جديد</Link>
        </div>

        <div class="utu-card mb-4 flex flex-wrap gap-2">
            <input v-model="search" @input="runFilter" type="text" placeholder="بحث بالاسم، الرمز، أو الهاتف…" class="utu-input w-64">
            <template v-if="!isSalesRep">
                <select v-model="areaFilter" @change="runFilter" class="utu-input w-40">
                    <option value="">كل المناطق</option>
                    <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.name }}</option>
                </select>
                <select v-model="repFilter" @change="runFilter" class="utu-input w-44">
                    <option value="">كل المندوبين</option>
                    <option v-for="r in reps" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </template>
            <select v-model="statusFilter" @change="runFilter" class="utu-input w-36">
                <option value="">كل الحالات</option>
                <option value="active">فعّال</option>
                <option value="inactive">غير فعّال</option>
            </select>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">الاسم</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الرمز</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الهاتف</th>
                        <th class="px-4 py-2.5 text-start font-semibold">المنطقة</th>
                        <th v-if="!isSalesRep" class="px-4 py-2.5 text-start font-semibold">المندوب</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الحالة</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in customers.data" :key="c.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">
                            {{ c.name }}
                            <span v-if="c.is_special_case" class="ms-1 rounded-utu bg-grey-2 px-1.5 py-0.5 text-[10px] text-grey-text">حالة خاصة</span>
                        </td>
                        <td class="px-4 py-2.5 text-grey-text">{{ c.code || '—' }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ c.phone || '—' }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ c.area?.name || '—' }}</td>
                        <td v-if="!isSalesRep" class="px-4 py-2.5 text-grey-text">{{ c.assignedRep?.name || '—' }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="c.active ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">
                                {{ c.active ? 'فعّال' : 'غير فعّال' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <Link :href="route('customers.edit', c.id)" class="utu-btn-ghost !px-3 !py-1.5">تعديل</Link>
                        </td>
                    </tr>
                    <tr v-if="customers.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-grey-text">لا يوجد عملاء بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <div v-if="customers.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in customers.links"
                :key="i"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-utu border border-grey-3 px-3 py-1.5 text-[12px]"
                :class="{ 'bg-ink text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
            />
        </div>
    </AppLayout>
</template>
