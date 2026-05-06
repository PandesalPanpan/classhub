<div
    class="w-full h-screen flex flex-col overflow-hidden"
    wire:poll.10s
    data-rooms="{{ json_encode($rooms) }}"
    data-events="{{ json_encode($events) }}"
>
    <div id="tv-calendar-header" class="w-full shrink-0 flex items-center justify-between px-4 py-2" style="background-color: #800000;" wire:ignore>
        <p style="margin: 0; color: #ffffff; font-size: 16px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;">
            ClassHub &mdash; Room Schedule
        </p>
        <p id="tv-calendar-date" style="margin: 0; color: #FFDF00; font-size: 16px; font-weight: 700;"></p>
    </div>
    <div id="tv-calendar" class="w-full flex-1 min-h-0" wire:ignore></div>
</div>

<script>
    (function() {
        window.tvCalendarInstance = null;

        window.updateTvCalendarDateDisplay = function() {
            const el = document.getElementById('tv-calendar-date');
            if (!el) {
                return;
            }

            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            el.textContent = now.toLocaleDateString('en-US', options);
        };

        window.updateTvCalendarDateDisplay();

        const container = document.querySelector('[data-rooms][data-events]');
        const rooms = container ? JSON.parse(container.dataset.rooms || '[]') : @json($rooms);
        const events = container ? JSON.parse(container.dataset.events || '[]') : @json($events);

        function initCalendar() {
            if (typeof window.initTvCalendar === 'function') {
                window.tvCalendarInstance = window.initTvCalendar(rooms, events);
            } else {
                setTimeout(initCalendar, 50);
            }
        }

        window.updateTvCalendar = function(newRooms, newEvents) {
            if (window.tvCalendarInstance) {
                const currentResources = window.tvCalendarInstance.getResources();
                const resourceIds = currentResources.map(r => r.id).sort().join(',');
                const newResourceIds = newRooms.map(r => r.id).sort().join(',');

                if (resourceIds !== newResourceIds) {
                    window.tvCalendarInstance.setOption('resources', newRooms);
                }

                const coloredEvents = window.withHashedColors
                    ? window.withHashedColors(newEvents)
                    : newEvents;
                window.tvCalendarInstance.removeAllEvents();
                if (coloredEvents && coloredEvents.length > 0) {
                    window.tvCalendarInstance.addEventSource(coloredEvents);
                }
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCalendar);
        } else {
            initCalendar();
        }

        document.addEventListener('livewire:init', () => {
            Livewire.hook('morph.updated', ({ el }) => {
                const container = el.querySelector('[data-rooms][data-events]') ||
                                 (el.hasAttribute('data-rooms') && el.hasAttribute('data-events') ? el : null);

                if (container && window.tvCalendarInstance) {
                    const rooms = JSON.parse(container.dataset.rooms || '[]');
                    const events = JSON.parse(container.dataset.events || '[]');
                    window.updateTvCalendar(rooms, events);
                    window.updateTvCalendarDateDisplay?.();
                }
            });
        });

        // Day change refresh
        (function() {
            const CHECK_INTERVAL = 60000;
            let dayChangeTimeout = null;
            let dayCheckInterval = null;
            let lastCheckedDate = null;

            function getTimeUntilMidnight() {
                const now = new Date();
                const midnight = new Date();
                midnight.setHours(24, 1, 0, 0);
                return midnight.getTime() - now.getTime();
            }

            function scheduleNextDayRefresh() {
                if (dayChangeTimeout) clearTimeout(dayChangeTimeout);
                dayChangeTimeout = setTimeout(() => {
                    const componentEl = document.querySelector('[wire\\:id]');
                    if (componentEl && typeof Livewire !== 'undefined') {
                        const wireId = componentEl.getAttribute('wire:id');
                        if (wireId) {
                            const component = Livewire.find(wireId);
                            if (component) component.$refresh();
                        }
                    }
                    lastCheckedDate = new Date().toDateString();
                    scheduleNextDayRefresh();
                }, getTimeUntilMidnight());
            }

            function checkDayChange() {
                const currentDate = new Date().toDateString();
                if (lastCheckedDate && lastCheckedDate !== currentDate) {
                    window.updateTvCalendarDateDisplay?.();
                    const componentEl = document.querySelector('[wire\\:id]');
                    if (componentEl && typeof Livewire !== 'undefined') {
                        const wireId = componentEl.getAttribute('wire:id');
                        if (wireId) {
                            const component = Livewire.find(wireId);
                            if (component) component.$refresh();
                        }
                    }
                }
                lastCheckedDate = currentDate;
            }

            lastCheckedDate = new Date().toDateString();
            scheduleNextDayRefresh();
            dayCheckInterval = setInterval(checkDayChange, CHECK_INTERVAL);

            window.addEventListener('beforeunload', function() {
                if (dayChangeTimeout) clearTimeout(dayChangeTimeout);
                if (dayCheckInterval) clearInterval(dayCheckInterval);
            });

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) checkDayChange();
            });
        })();
    })();
</script>

