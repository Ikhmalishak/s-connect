
<!-- <template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <SidebarProvider>
            <div class="flex h-screen">
                <!-- Enhanced Sidebar -->
                <div class="relative">
                    <AppSidebar class="h-full border-r border-white/20 bg-white/80 backdrop-blur-xl shadow-2xl" />
                </div>

                <!-- Main Content Area -->
                <div class="flex-1 flex flex-col overflow-hidden min-w-0">
                    <!-- Top Header Bar -->
                    <header class="bg-white/60 backdrop-blur-xl border-b border-white/20 shadow-sm">
                        <div class="flex items-center justify-between px-6 py-4 w-full">
                            <div class="flex items-center gap-4">
                                <SidebarTrigger class="lg:hidden p-2 hover:bg-white/50 rounded-lg transition-colors" />
                                <div class="hidden sm:block">
                                    <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                        Dashboard
                                    </h1>
                                    <p class="text-sm text-gray-600">Welcome back, let's build something amazing</p>
                                </div>
                            </div>

                            <!-- User Profile Section -->
                            <div class="flex items-center gap-4">
                                <!-- Quick Actions -->
                                <div class="hidden md:flex items-center gap-2">
                                    <button class="p-2 hover:bg-white/50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12"></path>
                                        </svg>
                                    </button>
                                    <button class="p-2 hover:bg-white/50 rounded-lg transition-colors">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5v-12"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- User Dropdown -->
                                <div class="relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button class="flex items-center gap-3 px-4 py-2 bg-white/50 hover:bg-white/70 rounded-xl transition-all duration-200 border border-white/20">
                                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                                    <span class="text-white font-medium text-sm">
                                                        {{ $page.props.auth.user.name.charAt(0) }}
                                                    </span>
                                                </div>
                                                <div class="hidden sm:block text-left">
                                                    <p class="text-sm font-medium text-gray-900">{{ $page.props.auth.user.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</p>
                                                </div>
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <div class="p-2">
                                                <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    Profile
                                                </DropdownLink>
                                                <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-red-50 text-red-600 w-full">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                    </svg>
                                                    Log Out
                                                </DropdownLink>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Main Content -->
                    <main class="flex-1 overflow-auto w-full">
                        <div class="p-6 space-y-6 w-full max-w-none">
                            <!-- Welcome Section -->
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-6 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-2xl font-bold mb-2">Good morning, {{ $page.props.auth.user.name }}!</h2>
                                        <p class="text-blue-100">Here's what's happening with your projects today.</p>
                                    </div>
                                    <div class="hidden lg:block">
                                        <div class="w-24 h-24 bg-white/20 rounded-full flex items-center justify-center">
                                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">Total Projects</p>
                                            <p class="text-2xl font-bold text-gray-900">24</p>
                                        </div>
                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center">
                                        <span class="text-green-600 text-sm font-medium">+12%</span>
                                        <span class="text-gray-500 text-sm ml-2">from last month</span>
                                    </div>
                                </div>

                                <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">Active Users</p>
                                            <p class="text-2xl font-bold text-gray-900">1,429</p>
                                        </div>
                                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center">
                                        <span class="text-green-600 text-sm font-medium">+5.4%</span>
                                        <span class="text-gray-500 text-sm ml-2">from last week</span>
                                    </div>
                                </div>

                                <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">Revenue</p>
                                            <p class="text-2xl font-bold text-gray-900">$12,426</p>
                                        </div>
                                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center">
                                        <span class="text-red-600 text-sm font-medium">-2.1%</span>
                                        <span class="text-gray-500 text-sm ml-2">from last month</span>
                                    </div>
                                </div>

                                <div class="bg-white/70 backdrop-blur-sm rounded-xl p-6 border border-white/20 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-600">Growth Rate</p>
                                            <p class="text-2xl font-bold text-gray-900">24.5%</p>
                                        </div>
                                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex items-center">
                                        <span class="text-green-600 text-sm font-medium">+8.2%</span>
                                        <span class="text-gray-500 text-sm ml-2">from last quarter</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content Area -->
                            <div class="bg-white/70 backdrop-blur-sm rounded-xl border border-white/20 p-6 min-h-[400px] w-full">
                                <slot />
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </SidebarProvider>
    </div>
</template> -->