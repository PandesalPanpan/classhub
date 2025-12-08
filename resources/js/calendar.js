import { Calendar } from '@fullcalendar/core';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';

window.initClassroomCalendar = function(rooms, events) {
    const calendarEl = document.getElementById('classroom-calendar');
    
    if (!calendarEl || calendarEl.dataset.initialized) return;
    
    calendarEl.dataset.initialized = 'true';
    
    const calendar = new Calendar(calendarEl, {
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        plugins: [resourceTimelinePlugin, interactionPlugin],
        initialView: 'resourceTimelineDay',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'resourceTimelineDay,resourceTimelineWeek,resourceTimelineMonth'
        },
        resources: rooms,
        events: events,
        resourceAreaWidth: '15%',
        slotMinTime: '08:00:00',
        slotMaxTime: '18:00:00',
        slotDuration: '00:30:00',
        height: 'auto',
        aspectRatio: 1.8,
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        nowIndicator: true,
        resourceLabelContent: function(arg) {
            return arg.resource.title;
        },
        eventContent: function(arg) {
            return {
                html: '<div class="fc-event-title">' + arg.event.title + '</div>'
            };
        },
    });

    calendar.render();
    
    return calendar;
};

