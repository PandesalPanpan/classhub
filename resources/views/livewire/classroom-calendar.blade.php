<div 
    class="w-full p-6" 
    wire:poll.10s
    data-rooms="{{ json_encode($rooms) }}"
    data-events="{{ json_encode($events) }}"
>
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
    
    <div id="classroom-calendar" class="w-full" wire:ignore></div>
</div>

<script>
    (function() {
        // Store calendar instance globally so we can update it
        window.classroomCalendarInstance = null;
        
        const container = document.querySelector('[data-rooms][data-events]');
        const rooms = container ? JSON.parse(container.dataset.rooms || '[]') : @json($rooms);
        const events = container ? JSON.parse(container.dataset.events || '[]') : @json($events);
        
        function initCalendar() {
            if (typeof window.initClassroomCalendar === 'function') {
                window.classroomCalendarInstance = window.initClassroomCalendar(rooms, events);
            } else {
                // Wait a bit for the script to load
                setTimeout(initCalendar, 50);
            }
        }
        
        // Expose update function globally for Alpine.js to call
        window.updateClassroomCalendar = function(newRooms, newEvents) {
            if (window.classroomCalendarInstance) {
                // Update resources (rooms) if they changed
                const currentResources = window.classroomCalendarInstance.getResources();
                const resourceIds = currentResources.map(r => r.id).sort().join(',');
                const newResourceIds = newRooms.map(r => r.id).sort().join(',');
                
                if (resourceIds !== newResourceIds) {
                    window.classroomCalendarInstance.setOption('resources', newRooms);
                }
                
                // Update events
                window.classroomCalendarInstance.removeAllEvents();
                if (newEvents && newEvents.length > 0) {
                    window.classroomCalendarInstance.addEventSource(newEvents);
                }
            }
        };
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCalendar);
        } else {
            initCalendar();
        }
        
        // Listen for Livewire component updates (from polling)
        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el }) => {
                // Check if this is our component by looking for the data attributes
                const container = el.querySelector('[data-rooms][data-events]') || 
                                 (el.hasAttribute('data-rooms') && el.hasAttribute('data-events') ? el : null);
                
                if (container && window.classroomCalendarInstance) {
                    const rooms = JSON.parse(container.dataset.rooms || '[]');
                    const events = JSON.parse(container.dataset.events || '[]');
                    window.updateClassroomCalendar(rooms, events);
                }
            });
        });
    })();
</script>

