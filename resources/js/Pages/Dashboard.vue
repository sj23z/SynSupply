<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    role: String,
    unlinked: Boolean,
    repName: String,
    myCustomersCount: Number,
    myCustomers: Array,
    upcomingFollowUps: Array,
    recentVisits: Array,
    recentInvoices: Array,
    salesToday: Number,
    salesMonth: Number,
    netSalesMonth: Number,
    outstandingReceivables: Number,
    cashOnHand: Number,
    monthlyExpenses: Number,
    inventoryValue: Number,
    grossProfitMonth: Number,
    operatingExpensesMonth: Number,
    operatingProfitMonth: Number,
    negativeStockCount: Number,
    lowStockCount: Number,
    expiringSoonCount: Number,
    topProducts: Array,
    topCustomers: Array,
    lowStockProducts: Array,
});

function completeFollowUp(visitId) {
    router.patch(route('customer-visits.complete-follow-up', visitId));
}
function formatIqd(v) { return new Intl.NumberFormat('en-US').format(v || 0) + ' د.ع'; }
const payLabels = { unpaid: 'غير مدفوعة', partial: 'جزئية', paid: 'مدفوعة' };
</script>

<template>
    <AppLayout>
        <template v-if="role === 'sales_rep'">
            <div v-if="unlinked" class="utu-card">
                <p class="text-[13px] text-grey-text">
                    حسابك غير مرتبط بسجل مندوب مبيعات بعد. يرجى التواصل مع الإدارة.
                </p>
            </div>
            <template v-else>
                <h1 class="mb-4 text-base font-bold text-ink">مرحباً، {{ repName }}</h1>

                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="utu-card">
                        <p class="text-[12px] text-grey-text">عدد عملائي</p>
                        <p class="mt-1 text-2xl font-bold text-ink">{{ myCustomersCount }}</p>
                    </div>
                    <div class="utu-card">
                        <p class="text-[12px] text-grey-text">متابعات قادمة</p>
                        <p class="mt-1 text-2xl font-bold text-ink">{{ upcomingFollowUps.length }}</p>
                    </div>
                    <div class="utu-card">
                        <p class="text-[12px] text-grey-text">زيارات حديثة</p>
                        <p class="mt-1 text-2xl font-bold text-ink">{{ recentVisits.length }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="utu-card">
                        <h2 class="mb-3 text-[13px] font-bold text-ink">المتابعات القادمة</h2>
                        <ul class="space-y-2">
                            <li v-for="f in upcomingFollowUps" :key="f.id" class="flex items-center justify-between rounded-utu bg-grey-1 px-3 py-2 text-[12.5px]">
                                <div>
                                    <Link :href="route('customers.edit', f.customer.id)" class="font-medium text-ink hover:underline">{{ f.customer.name }}</Link>
                                    <span class="ms-2 text-grey-text">{{ f.follow_up_date }}</span>
                                </div>
                                <button class="utu-btn-ghost !px-2 !py-1 !text-[11px]" @click="completeFollowUp(f.id)">إنجاز</button>
                            </li>
                            <li v-if="upcomingFollowUps.length === 0" class="text-[12.5px] text-grey-text">لا توجد متابعات قادمة.</li>
                        </ul>
                    </div>

                    <div class="utu-card">
                        <h2 class="mb-3 text-[13px] font-bold text-ink">آخر الزيارات</h2>
                        <ul class="space-y-2">
                            <li v-for="v in recentVisits" :key="v.id" class="rounded-utu bg-grey-1 px-3 py-2 text-[12.5px]">
                                <div class="flex items-center justify-between">
                                    <Link :href="route('customers.edit', v.customer.id)" class="font-medium text-ink hover:underline">{{ v.customer.name }}</Link>
                                    <span class="text-grey-text">{{ v.visit_date }}</span>
                                </div>
                                <p class="mt-1 text-grey-text">{{ v.notes }}</p>
                            </li>
                            <li v-if="recentVisits.length === 0" class="text-[12.5px] text-grey-text">لا توجد زيارات مسجلة بعد.</li>
                        </ul>
                    </div>

                    <div class="utu-card col-span-2">
                        <h2 class="mb-3 text-[13px] font-bold text-ink">حالة فواتير عملائي</h2>
                        <ul class="space-y-1">
                            <li v-for="i in recentInvoices" :key="i.id" class="flex items-center justify-between rounded-utu bg-grey-1 px-3 py-2 text-[12.5px]">
                                <span>{{ i.invoice_number || '(مسودة)' }} — {{ i.customer?.name }}</span>
                                <span class="rounded-utu bg-gold-soft/40 px-2 py-0.5 text-[11px] font-semibold text-ink">{{ payLabels[i.payment_status] }}</span>
                            </li>
                            <li v-if="recentInvoices.length === 0" class="text-[12.5px] text-grey-text">لا توجد فواتير بعد.</li>
                        </ul>
                    </div>
                </div>
            </template>
        </template>

        <template v-else-if="role === 'admin' || role === 'owner'">
            <h1 class="mb-4 text-base font-bold text-ink">لوحة التحكم</h1>
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="utu-card"><p class="text-[12px] text-grey-text">مبيعات اليوم</p><p class="mt-1 text-lg font-bold">{{ formatIqd(salesToday) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">مبيعات الشهر</p><p class="mt-1 text-lg font-bold">{{ formatIqd(salesMonth) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">صافي المبيعات (الشهر)</p><p class="mt-1 text-lg font-bold">{{ formatIqd(netSalesMonth) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">الذمم المستحقة</p><p class="mt-1 text-lg font-bold">{{ formatIqd(outstandingReceivables) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">النقد في الصندوق</p><p class="mt-1 text-lg font-bold" :class="cashOnHand >= 0 ? 'text-ink' : 'text-danger'">{{ formatIqd(cashOnHand) }}</p></div>
                <template v-if="role === 'admin'">
                    <div class="utu-card"><p class="text-[12px] text-grey-text">مصروفات الشهر</p><p class="mt-1 text-lg font-bold">{{ formatIqd(monthlyExpenses) }}</p></div>
                </template>
                <template v-if="role === 'owner'">
                    <div class="utu-card"><p class="text-[12px] text-grey-text">قيمة المخزون</p><p class="mt-1 text-lg font-bold">{{ formatIqd(inventoryValue) }}</p></div>
                    <div class="utu-card"><p class="text-[12px] text-grey-text">الربح الإجمالي (الشهر)</p><p class="mt-1 text-lg font-bold text-gold">{{ formatIqd(grossProfitMonth) }}</p></div>
                    <div class="utu-card"><p class="text-[12px] text-grey-text">المصروفات التشغيلية (الشهر)</p><p class="mt-1 text-lg font-bold">{{ formatIqd(operatingExpensesMonth) }}</p></div>
                    <div class="utu-card"><p class="text-[12px] text-grey-text">الربح التشغيلي (الشهر)</p><p class="mt-1 text-lg font-bold" :class="operatingProfitMonth >= 0 ? 'text-gold' : 'text-danger'">{{ formatIqd(operatingProfitMonth) }}</p></div>
                </template>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <Link :href="route('inventory.index')" class="utu-card block hover:border-ink">
                    <p class="text-[12px] text-grey-text">مخزون سالب</p>
                    <p class="mt-1 text-lg font-bold" :class="negativeStockCount > 0 ? 'text-danger' : 'text-ink'">{{ negativeStockCount }}</p>
                </Link>
                <Link :href="route('inventory.index')" class="utu-card block hover:border-ink">
                    <p class="text-[12px] text-grey-text">مخزون منخفض</p>
                    <p class="mt-1 text-lg font-bold">{{ lowStockCount }}</p>
                </Link>
                <Link :href="route('inventory.index')" class="utu-card block hover:border-ink">
                    <p class="text-[12px] text-grey-text">قرب انتهاء الصلاحية (٩٠ يوم)</p>
                    <p class="mt-1 text-lg font-bold">{{ expiringSoonCount }}</p>
                </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="utu-card">
                    <h2 class="mb-3 text-[13px] font-bold text-ink">الأكثر مبيعاً هذا الشهر</h2>
                    <ul class="space-y-1">
                        <li v-for="p in topProducts" :key="p.product_id" class="flex justify-between text-[12.5px]">
                            <span>{{ p.product?.name }}</span><span class="text-grey-text">{{ p.total_qty }}</span>
                        </li>
                        <li v-if="topProducts.length === 0" class="text-[12.5px] text-grey-text">لا توجد بيانات بعد.</li>
                    </ul>
                </div>
                <div class="utu-card">
                    <h2 class="mb-3 text-[13px] font-bold text-ink">أفضل العملاء هذا الشهر</h2>
                    <ul class="space-y-1">
                        <li v-for="c in topCustomers" :key="c.customer_id" class="flex justify-between text-[12.5px]">
                            <span>{{ c.customer?.name }}</span><span class="text-grey-text">{{ formatIqd(c.total) }}</span>
                        </li>
                        <li v-if="topCustomers.length === 0" class="text-[12.5px] text-grey-text">لا توجد بيانات بعد.</li>
                    </ul>
                </div>
            </div>
        </template>

        <template v-else>
            <h1 class="mb-4 text-base font-bold text-ink">لوحة التحكم</h1>
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="utu-card"><p class="text-[12px] text-grey-text">مبيعات اليوم</p><p class="mt-1 text-lg font-bold">{{ formatIqd(salesToday) }}</p></div>
                <div class="utu-card"><p class="text-[12px] text-grey-text">الذمم المستحقة</p><p class="mt-1 text-lg font-bold">{{ formatIqd(outstandingReceivables) }}</p></div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="utu-card">
                    <h2 class="mb-3 text-[13px] font-bold text-ink">منتجات منخفضة المخزون</h2>
                    <ul class="space-y-1">
                        <li v-for="p in lowStockProducts" :key="p.id" class="flex justify-between text-[12.5px]">
                            <span>{{ p.name }}</span><span class="text-gold">{{ p.cached_stock_qty }}</span>
                        </li>
                        <li v-if="lowStockProducts.length === 0" class="text-[12.5px] text-grey-text">لا توجد تنبيهات حالياً.</li>
                    </ul>
                </div>
                <div class="utu-card">
                    <h2 class="mb-3 text-[13px] font-bold text-ink">آخر الفواتير</h2>
                    <ul class="space-y-1">
                        <li v-for="i in recentInvoices" :key="i.id" class="flex justify-between text-[12.5px]">
                            <span>{{ i.invoice_number || '(مسودة)' }} — {{ i.customer?.name }}</span>
                            <span class="text-grey-text">{{ formatIqd(i.grand_total) }}</span>
                        </li>
                        <li v-if="recentInvoices.length === 0" class="text-[12.5px] text-grey-text">لا توجد فواتير بعد.</li>
                    </ul>
                </div>
            </div>
        </template>
    </AppLayout>
</template>
