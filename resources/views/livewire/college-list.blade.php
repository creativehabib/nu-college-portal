<div>
    <!-- Main Glassmorphism Card Wrapper -->
    <div class="bg-white/90 dark:bg-[#161615]/90 backdrop-blur-xl shadow-xl shadow-slate-200/40 dark:shadow-none rounded-2xl border border-slate-200/60 dark:border-zinc-800 p-5 sm:p-8 overflow-hidden">

        <!-- Top Control Bar (Search) -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div class="w-full sm:w-80 relative group">
                <flux:input
                    wire:model.live="search"
                    icon="magnifying-glass"
                    placeholder="Search by Code, Name, or Email..."
                    class="w-full transition-shadow group-hover:shadow-md group-focus-within:shadow-md group-focus-within:shadow-indigo-500/10"
                />
            </div>

            <div class="text-sm font-medium text-slate-500 dark:text-zinc-400 bg-slate-100 dark:bg-zinc-800 px-4 py-2 rounded-lg">
                Total Found: <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ $colleges->total() }}</span>
            </div>
        </div>

        <!-- College Table (Premium UX) -->
        <div class="border border-slate-200 dark:border-zinc-800 rounded-xl overflow-hidden relative">

            <!-- Loading Overlay (সার্চ করার সময় টেবিল হালকা ব্লার হবে) -->
            <div wire:loading wire:target="search" class="absolute inset-0 bg-white/50 dark:bg-[#161615]/50 backdrop-blur-[2px] z-10 flex items-center justify-center">
                <div class="px-4 py-2 bg-white dark:bg-zinc-800 shadow-md rounded-lg flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-sm font-medium">
                    <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Searching...
                </div>
            </div>

            <flux:table>
                <!-- Header with distinct background and styling -->
                <flux:table.columns class="bg-slate-50/80 dark:bg-zinc-800/50 border-b border-slate-200 dark:border-zinc-800">
                    <flux:table.column class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Code</flux:table.column>
                    <flux:table.column class="text-xs font-semibold text-slate-500 uppercase tracking-wider">College Name</flux:table.column>
                    <flux:table.column class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact Email</flux:table.column>
                    <flux:table.column class="text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Action</flux:table.column>
                </flux:table.columns>

                <flux:table.rows class="divide-y divide-slate-100 dark:divide-zinc-800/50">
                    @forelse($colleges as $college)
                        <flux:table.row class="transition-colors hover:bg-slate-50/80 dark:hover:bg-zinc-800/30 group">

                            <!-- Highlighted Code -->
                            <flux:table.cell>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-zinc-300 border border-slate-200/60 dark:border-zinc-700/60 group-hover:bg-white dark:group-hover:bg-zinc-700 transition-colors">
                                    {{ $college->college_code }}
                                </span>
                            </flux:table.cell>

                            <!-- College Name -->
                            <flux:table.cell class="font-semibold text-slate-800 dark:text-zinc-200">
                                {{ $college->college_name }}
                            </flux:table.cell>

                            <!-- Clickable Email -->
                            <flux:table.cell>
                                @if($college->email)
                                    <a href="mailto:{{ $college->email }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 transition-colors">
                                        <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        {{ $college->email }}
                                    </a>
                                @else
                                    <span class="text-sm text-slate-400 italic">N/A</span>
                                @endif
                            </flux:table.cell>

                            <!-- Action Button -->
                            <flux:table.cell class="text-center">
                                <button wire:click="viewDetails({{ $college->id }})" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:hover:bg-indigo-500/20 rounded-lg transition-all focus:ring-2 focus:ring-indigo-500 focus:outline-none shadow-sm shadow-indigo-100 dark:shadow-none">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    View
                                </button>
                            </flux:table.cell>

                        </flux:table.row>
                    @empty
                        <!-- Empty State -->
                        <flux:table.row>
                            <flux:table.cell colspan="4">
                                <div class="py-16 text-center flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 bg-slate-100 dark:bg-zinc-800 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-slate-900 dark:text-white">No colleges found</h3>
                                    <p class="text-sm text-slate-500 dark:text-zinc-400 mt-1">Try adjusting your search criteria.</p>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <!-- Pagination -->
        <div class="mt-6 border-t border-slate-100 dark:border-zinc-800 pt-4">
            {{ $colleges->links() }}
        </div>

        <!-- Beautiful Details Modal -->
        <flux:modal wire:model="isModalOpen" class="w-full max-w-5xl">
            @if($selectedCollege)
                <div class="bg-white dark:bg-[#161615] rounded-2xl overflow-hidden">

                    <!-- Modal Header (Gradient) -->
                    <div class="relative px-6 py-8 bg-gradient-to-r from-indigo-600 to-violet-600 overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 20px 20px;"></div>

                        <div class="relative z-10 flex flex-col items-center text-center">
                            <span class="px-3 py-1 text-xs font-bold tracking-wider text-indigo-700 bg-white rounded-full uppercase mb-3 shadow-sm">
                                College Code: {{ $selectedCollege->college_code }}
                            </span>
                            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">
                                {{ $selectedCollege->college_name }}
                            </h2>
                            <p class="mt-2 text-indigo-100 flex items-center gap-1.5 font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                {{ $selectedCollege->userDetail->college_Location ?? 'Location Unavailable' }}
                            </p>
                        </div>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-8 bg-slate-50 dark:bg-[#111] max-h-[65vh] overflow-y-auto">

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                            <!-- Information Group 1 -->
                            <div class="bg-white dark:bg-[#161615] rounded-xl border border-slate-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-800/30 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    <h3 class="font-bold text-slate-800 dark:text-white">Primary Information</h3>
                                </div>
                                <div class="p-5">
                                    <dl class="space-y-4">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Establishment Year</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->userDetail->establish_Year ?? 'N/A' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Total Land Area</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->userDetail->total_Land ?? 'N/A' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Institution Type</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">
                                                @if(isset($selectedCollege->userDetail->col_type))
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $selectedCollege->userDetail->col_type == 'Y' ? 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400' }}">
                                                        {{ $selectedCollege->userDetail->col_type == 'Y' ? 'Government' : 'Non-Government' }}
                                                    </span>
                                                @else N/A @endif
                                            </dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">College EIIN</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->userDetail->eiin ?? 'N/A' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Website</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">
                                                @if($selectedCollege->userDetail && $selectedCollege->userDetail->website)
                                                    <a href="{{ $selectedCollege->userDetail->website }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                                                        Visit Site
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                    </a>
                                                @else N/A @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Information Group 2 -->
                            <div class="bg-white dark:bg-[#161615] rounded-xl border border-slate-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                                <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-800/30 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <h3 class="font-bold text-slate-800 dark:text-white">Contact Directory</h3>
                                </div>
                                <div class="p-5">
                                    <dl class="space-y-4">
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Principal</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0 text-left sm:text-right">
                                                {{ $selectedCollege->userDetail->principal_name ?? 'N/A' }}
                                                @if($selectedCollege->userDetail && $selectedCollege->userDetail->principal_mobile)
                                                    <div class="text-xs text-slate-500 font-normal mt-0.5">{{ $selectedCollege->userDetail->principal_mobile }}</div>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Vice-Principal</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->userDetail->v_principal_name ?? 'N/A' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Head Clerk</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0 text-left sm:text-right">
                                                {{ $selectedCollege->userDetail->clerkname ?? 'N/A' }}
                                                @if($selectedCollege->userDetail && $selectedCollege->userDetail->cleark_mobile)
                                                    <div class="text-xs text-slate-500 font-normal mt-0.5">{{ $selectedCollege->userDetail->cleark_mobile }}</div>
                                                @endif
                                            </dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Office Phone</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->userDetail->college_phone ?? 'N/A' }}</dd>
                                        </div>
                                        <div class="flex flex-col sm:flex-row sm:justify-between sm:border-b border-slate-50 dark:border-zinc-800 pb-3 last:border-0 last:pb-0">
                                            <dt class="text-sm font-medium text-slate-500 dark:text-zinc-400">Email Address</dt>
                                            <dd class="text-sm font-semibold text-slate-900 dark:text-zinc-200 mt-1 sm:mt-0">{{ $selectedCollege->email ?? 'N/A' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Courses Section -->
                        <div class="mt-6 bg-white dark:bg-[#161615] rounded-xl border border-slate-200 dark:border-zinc-800 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-slate-100 dark:border-zinc-800 bg-slate-50/50 dark:bg-zinc-800/30 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                <h3 class="font-bold text-slate-800 dark:text-white">Academic Programs & Seats</h3>
                            </div>

                            <div class="p-5">
                                @if($selectedCollege->collegeSubjects && $selectedCollege->collegeSubjects->count() > 0)
                                    @php
                                        $groupedSubjects = $selectedCollege->collegeSubjects->groupBy(function($item) {
                                            return $item->course->course_name ?? 'Unknown Course';
                                        });
                                    @endphp

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        @foreach($groupedSubjects as $courseName => $subjects)
                                            <div class="border border-slate-100 dark:border-zinc-800 rounded-xl overflow-hidden">
                                                <div class="bg-slate-100/50 dark:bg-zinc-800/50 px-4 py-3 border-b border-slate-100 dark:border-zinc-800">
                                                    <h4 class="font-semibold text-indigo-700 dark:text-indigo-400">{{ $courseName }}</h4>
                                                </div>
                                                <ul class="divide-y divide-slate-100 dark:divide-zinc-800">
                                                    @foreach($subjects as $item)
                                                        <li class="flex items-center justify-between px-4 py-3 hover:bg-slate-50 dark:hover:bg-zinc-800/30 transition-colors">
                                                            <div class="flex items-center gap-3">
                                                                <span class="px-2 py-1 bg-slate-100 dark:bg-zinc-800 text-slate-500 dark:text-zinc-400 text-xs font-mono rounded">
                                                                    {{ str_pad($item->subject->subject_code ?? '', 2, '0', STR_PAD_LEFT) }}
                                                                </span>
                                                                <span class="text-sm font-medium text-slate-800 dark:text-zinc-200">
                                                                    {{ $item->subject->subject_name ?? '' }}
                                                                </span>
                                                            </div>
                                                            <div>
                                                                @if($item->seat_capacity)
                                                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20">
                                                                        {{ $item->seat_capacity }} Seats
                                                                    </span>
                                                                @else
                                                                    <span class="text-xs text-slate-400 italic">N/A</span>
                                                                @endif
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="py-8 text-center text-slate-500 dark:text-zinc-400 flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        <p>No academic program information available.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-zinc-800 bg-white dark:bg-[#161615] flex justify-end">
                        <flux:button variant="primary" wire:click="closeModal" class="w-full sm:w-auto shadow-sm">
                            Close Details
                        </flux:button>
                    </div>
                </div>
            @endif
        </flux:modal>
    </div>
</div>
