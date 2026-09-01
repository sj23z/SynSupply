<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ invoices: Object, filters: Object, isSalesRep: Boolean, canCreate: Boolean });

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
function runFilter() {
    router.get(route('invoices.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v) + ' د.ع'; }

const statusLabels = { draft: 'مسودة', finalized: 'معتمدة', cancelled: 'ملغاة' };
const payLabels = { unpaid: 'غير مدفوعة', partial: 'جزئية', paid: 'مدفوعة' };
</script>

<template>
    <AppLayout>
        <FlashBanner />
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">الفواتير</h1>
            <div class="flex gap-2">
                <select v-model="status" @change="runFilter" class="utu-input w-36">
                    <option value="">كل الحالات</option>
                    <option value="draft">مسودة</option>
                    <option value="finalized">معتمدة</option>
                    <option value="cancelled">ملغاة</option>
                </select>
                <input v-model="search" @input="runFilter" type="text" placeholder="رقم الفاتورة…" class="utu-input w-48">
                <Link v-if="canCreate" :href="route('invoices.create')" class="utu-btn-gold">فاتورة جديدة</Link>
            </div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start">الرقم</th>
                        <th class="px-4 py-2.5 text-start">العميل</th>
                        <th class="px-4 py-2.5 text-start">التاريخ</th>
                        <th v-if="!isSalesRep" class="px-4 py-2.5 text-start">الإجمالي</th>
                        <th class="px-4 py-2.5 text-start">الحالة</th>
                        <th class="px-4 py-2.5 text-start">الدفع</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ inv.invoice_number || '(مسودة)' }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ inv.customer?.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ inv.invoice_date }}</td>
                        <td v-if="!isSalesRep" class="px-4 py-2.5">{{ formatIqd(inv.grand_total) }}</td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="{ 'bg-grey-2 text-grey-text': inv.status === 'draft', 'bg-gold-soft/40 text-ink': inv.status === 'finalized', 'bg-danger/10 text-danger': inv.status === 'cancelled' }">
                                {{ statusLabels[inv.status] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-grey-text">{{ payLabels[inv.payment_status] }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-end">
                            <Link v-if="!isSalesRep" :href="route('invoices.show', inv.id)" class="utu-btn-ghost !px-3 !py-1.5">عرض</Link>
                        </td>
                    </tr>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-grey-text">لا توجد فواتير بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <div v-if="invoices.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link v-for="(link, i) in invoices.links" :key="i" :href="link.url || '#'" v-html="link.label"
                class="rounded-utu border border-grey-3 px-3 py-1.5 text-[12px]"
                :class="{ 'bg-ink text-white': link.active, 'pointer-events-none opacity-40': !link.url }" />
        </div>
    </AppLayout>
</template>
