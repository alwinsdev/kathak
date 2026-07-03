<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Practice History') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rise-in grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-stat-card label="Total Sessions" :value="$stats->total" icon="sessions" />
                <x-stat-card label="This Week" :value="$stats->thisWeek" icon="calendar" />
                <x-stat-card label="Current Streak" :value="$stats->streak.' '.__('days')" icon="streak" />
                <x-stat-card label="Last Practice"
                    :value="$stats->lastPracticeDate?->format('d M Y') ?? '—'" icon="clock" />
            </div>

            {{-- Streak banner: the most motivating stat, promoted --}}
            @if ($stats->streak >= 3)
                <div class="rise-in-1 flex items-center gap-3 rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-5 py-3.5 text-white shadow-lg shadow-amber-500/25">
                    <svg class="h-6 w-6 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <p class="text-sm">
                        <span class="font-bold">{{ trans_choice(':count-day streak|:count-day streak', $stats->streak, ['count' => $stats->streak]) }}</span>
                        <span class="text-white/85"> — {{ __('keep it going!') }}</span>
                    </p>
                </div>
            @endif

            {{-- Practice activity calendar --}}
            <div x-data="practiceCalendar(@js($calendar))" class="rise-in-2 grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Calendar --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03] lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <h3 class="font-semibold text-gray-800">{{ __('Practice Calendar') }}</h3>
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 tabular-nums"
                                x-text="monthTotal + ' {{ __('sessions') }}'"></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" @click="prevMonth()" aria-label="{{ __('Previous month') }}"
                                class="rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <span class="w-36 text-center text-sm font-semibold text-gray-700" x-text="monthLabel"></span>
                            <button type="button" @click="nextMonth()" aria-label="{{ __('Next month') }}"
                                class="rounded-lg p-1.5 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-7 px-4 pt-4 text-center text-xs font-medium uppercase tracking-wide text-gray-400">
                        <template x-for="w in weekdays" :key="w"><div class="py-1" x-text="w"></div></template>
                    </div>

                    {{-- Day grid: heatmap intensity + keyboard navigation --}}
                    <div class="grid grid-cols-7 gap-1.5 p-4 pt-2" tabindex="0" role="grid" aria-label="{{ __('Practice calendar') }}"
                         @keydown.arrow-right.prevent="move(1)"
                         @keydown.arrow-left.prevent="move(-1)"
                         @keydown.arrow-down.prevent="move(7)"
                         @keydown.arrow-up.prevent="move(-7)">
                        <template x-for="cell in cells" :key="cell.key">
                            <div>
                                <template x-if="cell.day">
                                    <button type="button" @click="select(cell.date)"
                                        class="relative flex h-11 w-full items-center justify-center rounded-lg text-sm transition"
                                        :class="dayClass(cell.date)"
                                        :aria-label="ariaFor(cell)"
                                        :aria-pressed="cell.date === selected">
                                        <span x-text="cell.day"></span>
                                        <span x-show="count(cell.date) > 1"
                                            class="absolute bottom-1 text-[10px] font-semibold leading-none opacity-80"
                                            x-text="count(cell.date) + '×'"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Intensity legend --}}
                    <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 px-6 py-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5">
                            {{ __('Less') }}
                            <span class="h-3 w-3 rounded bg-gray-100"></span>
                            <span class="h-3 w-3 rounded bg-teal-200"></span>
                            <span class="h-3 w-3 rounded bg-teal-400"></span>
                            <span class="h-3 w-3 rounded bg-teal-600"></span>
                            {{ __('More') }}
                        </span>
                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded ring-1 ring-teal-400"></span>{{ __('Today') }}</span>
                    </div>
                </div>

                {{-- Selected-day detail --}}
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-900/[0.03]">
                    <div class="flex items-start justify-between gap-2 border-b border-gray-100 px-6 py-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Sessions on') }}</div>
                            <h3 class="mt-0.5 font-semibold text-gray-800" x-text="selectedLabel"></h3>
                        </div>
                        <span x-show="selectedSessions.length" x-cloak
                            class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-700 tabular-nums"
                            x-text="selectedSessions.length"></span>
                    </div>
                    <div class="px-6 py-4">
                        <template x-if="selectedSessions.length">
                            <ul class="space-y-3">
                                <template x-for="(s, i) in selectedSessions" :key="i">
                                    <li class="flex items-center gap-3">
                                        <template x-if="s.image">
                                            <img :src="s.image" :alt="s.mudra" class="h-10 w-10 shrink-0 rounded-lg object-cover ring-1 ring-gray-900/5">
                                        </template>
                                        <template x-if="!s.image">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" /></svg>
                                            </span>
                                        </template>
                                        <span class="min-w-0 flex-1 truncate font-medium text-gray-800" x-text="s.mudra"></span>
                                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold tabular-nums"
                                            :class="pillClass(s.confidence)"
                                            x-text="s.confidence !== null ? s.confidence + '%' : '—'"></span>
                                    </li>
                                </template>
                            </ul>
                        </template>
                        <div x-show="!selectedSessions.length" class="py-10 text-center text-sm text-gray-400">
                            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-xl bg-gray-50 text-gray-300">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
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
                get monthTotal() {
                    return this.cells.reduce((sum, c) => sum + (c.date ? this.count(c.date) : 0), 0);
                },
                count(date) { return (this.sessions[date] || []).length; },
                isToday(date) { return date === todayKey; },
                dayClass(date) {
                    const n = this.count(date);
                    let c = n === 0
                        ? 'text-gray-700 hover:bg-gray-100'
                        : n === 1
                            ? 'bg-teal-200 font-semibold text-teal-900 hover:bg-teal-300'
                            : n === 2
                                ? 'bg-teal-400 font-semibold text-white hover:bg-teal-500'
                                : 'bg-teal-600 font-bold text-white hover:bg-teal-700';
                    if (this.isToday(date) && n === 0) c += ' ring-1 ring-teal-400';
                    if (date === this.selected) c += ' ring-2 ring-teal-600 ring-offset-1';
                    return c;
                },
                ariaFor(cell) {
                    const n = this.count(cell.date);
                    return `${cell.day} ${this.monthLabel} — ${n} ${n === 1 ? 'session' : 'sessions'}`;
                },
                pillClass(conf) {
                    if (conf === null || conf === undefined) return 'bg-gray-100 text-gray-500';
                    if (conf >= 90) return 'bg-emerald-100 text-emerald-700';
                    if (conf >= 75) return 'bg-teal-100 text-teal-700';
                    return 'bg-amber-100 text-amber-700';
                },
                select(date) { this.selected = date; },
                move(deltaDays) {
                    const [y, m, d] = this.selected.split('-').map(Number);
                    const next = new Date(y, m - 1, d + deltaDays);
                    this.year = next.getFullYear();
                    this.month = next.getMonth();
                    this.selected = key(next.getFullYear(), next.getMonth(), next.getDate());
                },
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
