@once
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.15/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.15/main.min.css' rel='stylesheet' />
<link href='https://cdn.jsdelivr.net/npm/@fullcalendar/resource-timeline@6.1.15/main.min.css' rel='stylesheet' />
@endonce

<div class="w-full p-6" wire:ignore>
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Classroom Schedule</h2>
        <p class="text-gray-600 dark:text-gray-400">View the latest schedules for all classrooms</p>
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

