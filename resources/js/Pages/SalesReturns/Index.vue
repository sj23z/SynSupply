<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ returns: Object, filters: Object });
const status = ref(props.filters.status ?? '');
function runFilter() {
    router.get(route('sales-returns.index'), { status: status.value }, { preserveState: true, replace: true });
}
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }
const statusLabels = { draft: 'مسودة', finalized: 'معتمد', cancelled: 'ملغى' };
</script>

<template>
    <AppLayout>
        <FlashBanner />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">مردودات المبيعات</h1>
            <div class="flex gap-2">
                <select v-model="status" @change="runFilter" class="utu-input w-36">
                    <option value="">كل الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="finalized">معتمد</option>
                </select>
                <Link :href="route('sales-returns.create')" class="utu-btn-gold">مردود جديد</Link>
            </div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr><th class="px-4 py-2.5 text-start">الرقم</th><th class="px-4 py-2.5 text-start">العميل</th><th class="px-4 py-2.5 text-start">التاريخ</th><th class="px-4 py-2.5 text-start">القيمة</th><th class="px-4 py-2.5 text-start">الحالة</th><th class="px-4 py-2.5"></th></tr>
                </thead>
                <tbody>
                    <tr v-for="r in returns.data" :key="r.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ r.return_number || '(مسودة)' }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ r.customer?.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ r.return_date }}</td>
                        <td class="px-4 py-2.5">{{ formatIqd(r.total_value) }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="r.status === 'finalized' ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">{{ statusLabels[r.status] }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end"><Link :href="route('sales-returns.show', r.id)" class="utu-btn-ghost !px-3 !py-1.5">عرض</Link></td>
                    </tr>
                    <tr v-if="returns.data.length === 0"><td colspan="6" class="px-4 py-8 text-center text-grey-text">لا توجد مردودات بعد.</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </AppLayout>
</template>
