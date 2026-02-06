<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $siteName ?? config('app.name', 'HK Checklist') }}</title>

    <!-- Favicon -->
    @php
        $faviconPath = \App\Models\Setting::get('favicon_path');
        if ($faviconPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($faviconPath)) {
            $faviconUrl = asset('storage/' . $faviconPath);
            $faviconExt = strtolower(pathinfo($faviconPath, PATHINFO_EXTENSION));
            $faviconType = match($faviconExt) {
                'ico' => 'image/x-icon',
                'png' => 'image/png',
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                default => 'image/x-icon',
            };
        }
    @endphp
    @if (isset($faviconUrl))
        <link rel="icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" type="{{ $faviconType }}" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet" />

    <!-- Styles -->
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Dynamic Theme Color */
        :root {
            --theme-primary: {!! \App\Models\Setting::get('theme_color', '#842eb8') !!};
            --button-primary-color: {!! \App\Models\Setting::get('button_primary_color') ?: \App\Models\Setting::get('theme_color', '#842eb8') !!};
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    <div x-data="mainState" :class="{ dark: isDarkMode }" x-on:resize.window="handleWindowResize" x-cloak>
        <div class="min-h-screen text-gray-900 bg-gray-100 dark:bg-dark-eval-0 dark:text-gray-200">
            <!-- Sidebar -->
            <x-sidebar.sidebar />

            <!-- Page Wrapper -->
            <div class="flex flex-col min-h-screen"
                :class="{
                    'lg:ml-64': isSidebarOpen,
                    'md:ml-16': !isSidebarOpen
                }"
                style="transition-property: margin; transition-duration: 150ms;">

                <!-- Navbar -->
                <x-navbar />

                <!-- Page Heading -->
                <header>
                    <div class="p-4 sm:p-6">
                        {{ $header }}
                    </div>
                </header>

                <!-- Page Content -->
                <main class="px-4 sm:px-6 flex-1 pb-20 md:pb-6">
                    <x-flash.ok :timeout="6000" />
                    <x-flash.error :timeout="6000" />
                    {{ $slot }}
                </main>

                <!-- Toast Notification Container -->
                <div
                    x-data="{
                        toasts: [],
                        addToast(type, message) {
                            const id = Date.now()
                            this.toasts.push({ id, type, message })
                            setTimeout(() => this.removeToast(id), 5000)
                        },
                        removeToast(id) {
                            this.toasts = this.toasts.filter(t => t.id !== id)
                        }
                    }"
                    x-on:toast.window="addToast($event.detail.type, $event.detail.message)"
                    class="fixed bottom-6 right-6 z-50 space-y-3"
                    style="max-width: 400px;"
                >
                    <template x-for="toast in toasts" :key="toast.id">
                        <div
                            x-show="true"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-2"
                            :class="{
                                'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-300': toast.type === 'error',
                                'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300': toast.type === 'success',
                                'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300': toast.type === 'info',
                                'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/30 dark:border-amber-800 dark:text-amber-300': toast.type === 'warning'
                            }"
                            class="rounded-lg border shadow-lg px-4 py-3 flex items-start gap-3"
                            role="alert"
                        >
                            <svg
                                x-show="toast.type === 'error'"
                                class="h-5 w-5 mt-0.5 flex-none"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 6a1 1 0 112 0v5a1 1 0 11-2 0V6zm1 9a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 15z" clip-rule="evenodd" />
                            </svg>
                            <svg
                                x-show="toast.type === 'success'"
                                class="h-5 w-5 mt-0.5 flex-none"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 10-1.214-.882l-3.22 4.43L7.4 10.4a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.14-.09l3.877-5.68z" clip-rule="evenodd" />
                            </svg>
                            <svg
                                x-show="toast.type === 'info'"
                                class="h-5 w-5 mt-0.5 flex-none"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <svg
                                x-show="toast.type === 'warning'"
                                class="h-5 w-5 mt-0.5 flex-none"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1 text-sm font-medium" x-text="toast.message"></div>
                            <button
                                type="button"
                                @click="removeToast(toast.id)"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                aria-label="Dismiss"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 8.586L4.293 2.879A1 1 0 102.879 4.293L8.586 10l-5.707 5.707a1 1 0 001.414 1.414L10 11.414l5.707 5.707a1 1 0 001.414-1.414L11.414 10l5.707-5.707A1 1 0 0015.707 2.88L10 8.586z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Page Footer -->
                <x-footer />
            </div>
        </div>
    </div>
</body>

</html>
