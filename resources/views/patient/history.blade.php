<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Practice History') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Total Sessions" :value="$stats->total" icon="sessions" />
                <x-stat-card label="This Week" :value="$stats->thisWeek" icon="calendar" />
                <x-stat-card label="Current Streak" :value="$stats->streak.' '.__('days')" icon="streak" />
                <x-stat-card label="Last Practice"
                    :value="$stats->lastPracticeDate?->format('d M Y') ?? '—'" icon="clock" />
            </div>

            {{-- Practice activity calendar --}}
            <div x-data="practiceCalendar(@js($calendar))" class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Calendar --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h3 class="font-semibold text-gray-800">{{ __('Practice Calendar') }}</h3>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="prevMonth()" aria-label="{{ __('Previous month') }}"
                                class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <span class="w-36 text-center text-sm font-semibold text-gray-700" x-text="monthLabel"></span>
                            <button type="button" @click="nextMonth()" aria-label="{{ __('Next month') }}"
                                class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 px-4 pt-4 text-center text-xs font-medium uppercase tracking-wide text-gray-400">
                        <template x-for="w in weekdays" :key="w"><div class="py-1" x-text="w"></div></template>
                    </div>

                    <div class="grid grid-cols-7 gap-1.5 p-4 pt-2">
                        <template x-for="cell in cells" :key="cell.key">
                            <div>
                                <template x-if="cell.day">
                                    <button type="button" @click="select(cell.date)"
                                        class="relative flex h-11 w-full items-center justify-center rounded-lg text-sm transition"
                                        :class="dayClass(cell.date)">
                                        <span x-text="cell.day"></span>
                                        <span x-show="count(cell.date) > 1"
                                            class="absolute bottom-1 text-[10px] font-semibold leading-none"
                                            x-text="count(cell.date) + '×'"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-4 border-t border-gray-100 px-6 py-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-teal-500"></span>{{ __('Practised') }}</span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded ring-1 ring-teal-400"></span>{{ __('Today') }}</span>
                    </div>
                </div>

                {{-- Selected-day detail --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Sessions on') }}</div>
                        <h3 class="mt-0.5 font-semibold text-gray-800" x-text="selectedLabel"></h3>
                    </div>
                    <div class="px-6 py-4">
                        <template x-if="selectedSessions.length">
                            <ul class="space-y-3">
                                <template x-for="(s, i) in selectedSessions" :key="i">
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="font-medium text-gray-800" x-text="s.mudra"></span>
                                        <span class="shrink-0 rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-semibold text-teal-700"
                                            x-text="s.confidence !== null ? s.confidence + '%' : '—'"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <div x-show="!selectedSessions.length" class="py-10 text-center text-sm text-gray-400">
                            <div class="mb-1 text-2xl">🧘</div>
                            {{ __('No practice on this day.') }}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function practiceCalendar(data) {
            const pad = (n) => String(n).padStart(2, '0');
            const key = (y, m, d) => `${y}-${pad(m + 1)}-${pad(d)}`;
            const now = new Date();
            const todayKey = key(now.getFullYear(), now.getMonth(), now.getDate());

            return {
                sessions: data || {},
                weekdays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                year: now.getFullYear(),
                month: now.getMonth(),
                selected: todayKey,

                get monthLabel() {
                    return new Date(this.year, this.month, 1)
                        .toLocaleString(undefined, { month: 'long', year: 'numeric' });
                },
                get cells() {
                    const firstDay = new Date(this.year, this.month, 1).getDay();
                    const days = new Date(this.year, this.month + 1, 0).getDate();
                    const out = [];
                    for (let i = 0; i < firstDay; i++) out.push({ key: 'b' + i, day: null });
                    for (let d = 1; d <= days; d++) {
                        const date = key(this.year, this.month, d);
                        out.push({ key: date, day: d, date });
                    }
                    return out;
                },
                count(date) { return (this.sessions[date] || []).length; },
                isToday(date) { return date === todayKey; },
                dayClass(date) {
                    const practised = this.count(date) > 0;
                    let c = practised
                        ? 'bg-teal-500 text-white font-semibold hover:bg-teal-600'
                        : 'text-gray-700 hover:bg-gray-100';
                    if (this.isToday(date) && !practised) c += ' ring-1 ring-teal-400';
                    if (date === this.selected) c += ' ring-2 ring-teal-600 ring-offset-1';
                    return c;
                },
                select(date) { this.selected = date; },
                prevMonth() { this.month === 0 ? (this.month = 11, this.year--) : this.month--; },
                nextMonth() { this.month === 11 ? (this.month = 0, this.year++) : this.month++; },
                get selectedSessions() { return this.sessions[this.selected] || []; },
                get selectedLabel() {
                    const [y, m, d] = this.selected.split('-').map(Number);
                    return new Date(y, m - 1, d)
                        .toLocaleDateString(undefined, { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
                },
            };
        }
    </script>
</x-app-layout>
