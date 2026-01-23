import { Calendar } from '@fullcalendar/core';
import resourcePlugin from '@fullcalendar/resource';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';
import resourceTimeGridDay from '@fullcalendar/resource-timegrid';

const palette = [
    '#2563eb', // blue-600
    '#7c3aed', // violet-600
    '#0891b2', // cyan-600
    '#16a34a', // green-600
    '#d97706', // amber-600
    '#dc2626', // red-600
    '#0ea5e9', // sky-500
    '#9333ea', // purple-600
];

function hashTitleToColor(title) {
    let hash = 0;
    for (let i = 0; i < title.length; i++) {
        hash = ((hash << 5) - hash + title.charCodeAt(i)) | 0; // djb2-ish
    }
    const idx = Math.abs(hash) % palette.length;
    return palette[idx];
}

function withHashedColors(evts) {
    return (evts || []).map((evt) => {
        const color = hashTitleToColor(evt.title || '');
        return { ...evt, backgroundColor: color, borderColor: color };
    });
}

// Expose for Livewire updates
window.withHashedColors = withHashedColors;

window.initClassroomCalendar = function (rooms, events) {
    const calendarEl = document.getElementById('classroom-calendar');

    if (!calendarEl || calendarEl.dataset.initialized) return;

    calendarEl.dataset.initialized = 'true';

    const calendar = new Calendar(calendarEl, {
        schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
        plugins: [resourcePlugin, resourceTimelinePlugin, interactionPlugin, resourceTimeGridDay],
        initialView: 'resourceTimeGridDay',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'resourceTimelineDay'
        },
        resources: rooms,
        events: withHashedColors(events),
        resourceAreaWidth: '10%',
        slotMinTime: '07:00:00',
        slotMaxTime: '22:00:00',
        slotDuration: '00:30:00',
        slotLabelInterval: "00:30",
        height: 'auto',
        editable: false,
        selectable: false,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        nowIndicator: true,
        allDaySlot: false,
        expandRows: true,
        // displayEventTime: false,
        // contentHeight: 100,
        // eventMinHeight: 90,
        slotLabelClassNames: 'min-w-[100px]',
        // eventMinWidth: 100,
        // resourceLabelContent: function(arg) {
        //     return arg.resource.title;
        // },
        // eventContent: function(arg) {
        //     return {
        //         html: '<div class="fc-event-title">' + arg.event.title + '</div>'
        //     };
        // },
    });

    calendar.render();

    return calendar;
};
