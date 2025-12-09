<div class="w-full p-6" wire:ignore>
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">PUP Computer Engineering Classrooms Schedule</h2>
            <p class="text-gray-600 dark:text-gray-400">View the latest schedules for all classrooms</p>
        </div>
        <div class="flex items-center gap-3">
            @auth
                <button
                    type="button"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-md transition-colors cursor-pointer"
                >
                    Request Reservation
                </button>
                <form method="POST" action="{{ route('filament.app.auth.logout') }}" class="inline">
                    @csrf
                    <button
                        type="submit"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white font-medium rounded-md transition-colors cursor-pointer"
                    >
                        Logout
                    </button>
                </form>
            @else
                <a
                    href="{{ route('filament.app.auth.login') }}"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-md transition-colors cursor-pointer"
                >
                    Login
                </a>
            @endauth
        </div>
    </div>
    
    <div id="classroom-calendar" class="w-full"></div>
</div>

<script>
    (function() {
        const rooms = @json($rooms);
        const events = @json($events);
        
        function initCalendar() {
            if (typeof window.initClassroomCalendar === 'function') {
                window.initClassroomCalendar(rooms, events);
            } else {
                // Wait a bit for the script to load
                setTimeout(initCalendar, 50);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCalendar);
        } else {
            initCalendar();
        }
    })();
</script>

