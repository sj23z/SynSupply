<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FlashBanner from '@/Components/FlashBanner.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
    canManage: Boolean,
    isSalesRep: Boolean,
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
function runFilter() {
    router.get(route('products.index'), { search: search.value, status: status.value }, { preserveState: true, replace: true });
}

function toggleActive(product) {
    router.patch(route('products.toggle-active', product.id));
}

function formatIqd(v) {
    return new Intl.NumberFormat('en-US').format(v) + ' د.ع';
}
</script>

<template>
    <AppLayout>
        <FlashBanner />

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-base font-bold text-ink">المنتجات</h1>
            <div class="flex gap-2">
                <select v-model="status" @change="runFilter" class="utu-input w-36">
                    <option value="">كل الحالات</option>
                    <option value="active">فعّال</option>
                    <option value="inactive">غير فعّال</option>
                </select>
                <input v-model="search" @input="runFilter" type="text" placeholder="بحث بالاسم أو الرمز…" class="utu-input w-56">
                <Link v-if="canManage" :href="route('products.create')" class="utu-btn-gold">منتج جديد</Link>
            </div>
        </div>

        <div class="utu-card overflow-hidden !p-0">
            <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead class="bg-grey-1 text-[12px] text-grey-text">
                    <tr>
                        <th class="px-4 py-2.5 text-start font-semibold">الاسم</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الرمز (SKU)</th>
                        <th class="px-4 py-2.5 text-start font-semibold">سعر البيع</th>
                        <th class="px-4 py-2.5 text-start font-semibold">{{ isSalesRep ? 'التوفر' : 'الكمية المتوفرة' }}</th>
                        <th class="px-4 py-2.5 text-start font-semibold">الحالة</th>
                        <th v-if="canManage" class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id" class="border-t border-grey-2">
                        <td class="px-4 py-2.5 font-medium">{{ p.name }}</td>
                        <td class="px-4 py-2.5 text-grey-text">{{ p.sku || '—' }}</td>
                        <td class="px-4 py-2.5">{{ formatIqd(p.selling_price) }}</td>
                        <td v-if="isSalesRep" class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="p.available ? 'bg-gold-soft/40 text-ink' : 'bg-danger/10 text-danger'">
                                {{ p.available ? 'متوفر' : 'غير متوفر' }}
                            </span>
                        </td>
                        <td v-else class="px-4 py-2.5" :class="p.cached_stock_qty < 0 ? 'font-semibold text-danger' : 'text-grey-text'">
                            {{ p.cached_stock_qty }}
                            <span v-if="p.cached_stock_qty < 0" class="ms-1 rounded-utu bg-danger/10 px-1.5 py-0.5 text-[10px]">سالب</span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="rounded-utu px-2 py-0.5 text-[11px] font-semibold" :class="p.active ? 'bg-gold-soft/40 text-ink' : 'bg-grey-2 text-grey-text'">
                                {{ p.active ? 'فعّال' : 'غير فعّال' }}
                            </span>
                        </td>
                        <td v-if="canManage" class="whitespace-nowrap px-4 py-2.5 text-end">
                            <Link :href="route('products.edit', p.id)" class="utu-btn-ghost !px-3 !py-1.5">تعديل</Link>
                            <button class="utu-btn-ghost !px-3 !py-1.5" @click="toggleActive(p)">
                                {{ p.active ? 'إلغاء التفعيل' : 'تفعيل' }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="products.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-grey-text">لا توجد منتجات بعد.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div>

        <div v-if="products.links?.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, i) in products.links"
                :key="i"
                :href="link.url || '#'"
                v-html="link.label"
                class="rounded-utu border border-grey-3 px-3 py-1.5 text-[12px]"
                :class="{ 'bg-ink text-white': link.active, 'pointer-events-none opacity-40': !link.url }"
            />
        </div>
    </AppLayout>
</template>
